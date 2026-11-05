<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Models\VerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    public function index()
    {
        $store = \App\Support\CurrentStore::get();
        $sid = $store->id;

        $counts = [
            'pending'     => Order::where('store_id', $sid)->where('verification_status', 'pending')->count(),
            'no_response' => Order::where('store_id', $sid)->where('verification_status', 'no_response')->count(),
            'confirmed'   => Order::where('store_id', $sid)->where('verification_status', 'confirmed')->count(),
            'rejected'    => Order::where('store_id', $sid)->where('verification_status', 'rejected')->count(),
        ];

        // Orders that still need a customer confirmation
        $needs = Order::where('store_id', $sid)
            ->whereIn('verification_status', ['pending', 'no_response'])
            ->whereIn('status', ['pending', 'under_review', 'verification_sent'])
            ->orderByDesc('risk_score')->orderByDesc('placed_at')
            ->get();

        $attempts = VerificationLog::where('store_id', $sid)
            ->whereIn('order_id', $needs->pluck('id'))
            ->selectRaw('order_id, count(*) as c')->groupBy('order_id')
            ->pluck('c', 'order_id');

        $logs = VerificationLog::with('order')
            ->where('store_id', $sid)->orderByDesc('created_at')->take(15)->get();

        return view('verification.index', compact('counts', 'needs', 'attempts', 'logs'));
    }

    public function send(Request $request, Order $order)
    {
        $data = $request->validate(['channel' => 'required|in:whatsapp,sms,call']);
        $channel = $data['channel'];
        $store = $order->store;

        $token = Str::random(32);
        $otp = random_int(100000, 999999);
        $methods = ['whatsapp' => 'confirm_link', 'sms' => 'otp', 'call' => 'manual_call'];
        $tries = VerificationLog::where('order_id', $order->id)->count() + 1;

        VerificationLog::create([
            'store_id'    => $order->store_id,
            'order_id'    => $order->id,
            'customer_id' => $order->customer_id,
            'channel'     => $channel,
            'method'      => $methods[$channel],
            'status'      => $channel === 'call' ? 'pending' : 'sent',
            'otp_hash'    => $channel === 'sms' ? Hash::make((string) $otp) : null,
            'token_hash'  => hash('sha256', $token),
            'attempts'    => $tries,
            'sent_at'     => now(),
            'expires_at'  => now()->addHours(24),
        ]);

        // Simulated message: stored in notifications (no real WhatsApp/SMS yet)
        if ($channel !== 'call') {
            $template = DB::table('message_templates')
                ->whereNull('store_id')->where('code', 'order_confirmation')
                ->where('channel', $channel)->first();

            $body = str_replace(
                ['{customer_name}', '{order_number}', '{store_name}', '{link}'],
                [$order->customer_name, $order->order_number, $store->name, url('/verify/' . $token)],
                $template->body ?? 'Please confirm your order {order_number}.'
            );

            DB::table('notifications')->insert([
                'store_id'       => $order->store_id,
                'recipient_type' => 'customer',
                'customer_id'    => $order->customer_id,
                'order_id'       => $order->id,
                'channel'        => $channel,
                'template_code'  => 'order_confirmation',
                'body'           => $body,
                'status'         => 'sent',
                'sent_at'        => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $order->update(['verification_status' => 'pending', 'status' => 'verification_sent']);

               $label = $channel === 'call' ? 'Call task created' : 'Confirmation request sent via ' . $channel;
        $msg = $label . ' for ' . $order->order_number . ' (simulated).';
        if ($channel !== 'call' && app()->environment('local')) {
            $msg .= ' Customer link (local testing): ' . url('/verify/' . $token);
        }

        return redirect()->route('cod-verification')->with('status', $msg);
    }

    public function respond(Request $request, VerificationLog $log)
    {
        $data = $request->validate(['result' => 'required|in:confirmed,rejected,no_response']);
        $result = $data['result'];

        $log->update([
            'status'        => $result === 'no_response' ? 'expired' : $result,
            'response_text' => ['confirmed' => 'YES', 'rejected' => 'NO', 'no_response' => null][$result],
            'responded_at'  => $result === 'no_response' ? null : now(),
        ]);

        $order = $log->order;
        $order->verification_status = $result;
        if ($order->status === 'verification_sent') {
            $order->status = 'under_review';   // back to the merchant for the final decision
        }
        $order->save();

        if ($order->customer_id) {
            DB::statement('CALL sp_refresh_customer_stats(?)', [$order->customer_id]);
        }

        return redirect()->route('cod-verification')
            ->with('status', $order->order_number . ' marked as ' . str_replace('_', ' ', $result) . '.');
    }
}