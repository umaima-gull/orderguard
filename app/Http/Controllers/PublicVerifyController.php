<?php

namespace App\Http\Controllers;

use App\Models\VerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicVerifyController extends Controller
{
    public function show(string $token)
    {
        [$log, $state] = $this->resolve($token);

        return view('verify.show', [
            'log' => $log,
            'order' => $log?->order()->with('store', 'items')->first(),
            'state' => $state,
            'token' => $token,
        ]);
    }

    public function respond(Request $request, string $token)
    {
        $answer = $request->validate(['answer' => 'required|in:confirmed,rejected'])['answer'];

        [$log, $state] = $this->resolve($token);

        if ($state === 'open') {
            DB::transaction(function () use ($log, $answer, $request) {
                $log->update([
                    'status'        => $answer,
                    'response_text' => $answer === 'confirmed' ? 'YES' : 'NO',
                    'responded_at'  => now(),
                    'attempts'      => min(255, $log->attempts + 1),
                ]);

                $order = $log->order;
                $order->verification_status = $answer;
                if ($order->status === 'verification_sent') {
                    $order->status = 'under_review';   // back to the merchant for the final decision
                }
                $order->save();

                               \App\Services\Notifier::merchant(
                    $order->store,
                    'Customer ' . $answer . ' an order',
                    $order->customer_name . ' ' . $answer . ' order ' . $order->order_number . ' via the confirmation link.',
                    $order->id
                );

                if ($order->customer_id) {
                    DB::statement('CALL sp_refresh_customer_stats(?)', [$order->customer_id]);
                }
            });

            $state = $answer;
        }

        return view('verify.show', [
            'log' => $log,
            'order' => $log?->order()->with('store', 'items')->first(),
            'state' => $state,
            'token' => $token,
        ]);
    }

    /** Returns [log|null, state]: open | confirmed | rejected | expired | invalid */
    private function resolve(string $token): array
    {
        $log = VerificationLog::where('token_hash', hash('sha256', $token))->first();

        if (!$log) {
            return [null, 'invalid'];
        }
        if (in_array($log->status, ['confirmed', 'rejected'])) {
            return [$log, $log->status];
        }
        if ($log->status === 'expired' || ($log->expires_at && $log->expires_at->isPast())) {
            return [$log, 'expired'];
        }
        if (!in_array($log->status, ['pending', 'sent', 'delivered'])) {
            return [$log, 'invalid'];
        }

        return [$log, 'open'];
    }
}