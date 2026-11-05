<?php

namespace App\Http\Controllers;

use App\Models\RecoveryAction;
use App\Models\RecoveryCase;
use App\Models\ShipmentEvent;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecoveryController extends Controller
{
    private const CLOSED = ['delivered', 'returned', 'closed_unresolved'];

    public function index(Request $request)
    {
        $sid = \App\Support\CurrentStore::get()->id;

        $view = $request->query('view', 'open');
        $query = RecoveryCase::with('order.items.product', 'shipment.courier')->where('store_id', $sid);

        if ($view === 'open')       $query->whereNotIn('status', self::CLOSED);
        elseif ($view === 'closed') $query->whereIn('status', self::CLOSED);

        if ($type = $request->query('type')) $query->where('exception_type', $type);

        $cases = $query->orderByRaw('due_at is null')->orderBy('due_at')->simplePaginate(10)->withQueryString();

        $total     = RecoveryCase::where('store_id', $sid)->count();
        $recovered = RecoveryCase::where('store_id', $sid)->where('outcome', 'recovered')->count();

        $counts = [
            'open'      => RecoveryCase::where('store_id', $sid)->whereNotIn('status', self::CLOSED)->count(),
            'due'       => RecoveryCase::where('store_id', $sid)->whereNotIn('status', self::CLOSED)
                                ->where('due_at', '<=', now()->endOfDay())->count(),
            'recovered' => $recovered,
            'rate'      => $total ? round($recovered / $total * 100, 1) : 0,
            'revenue'   => (float) RecoveryCase::where('store_id', $sid)->where('outcome', 'recovered')->sum('revenue_recovered'),
        ];

        $types = ['customer_unavailable', 'phone_unreachable', 'address_issue', 'customer_refused',
                  'customer_postponed', 'area_not_serviceable', 'courier_issue', 'other'];
if ($request->ajax()) {
    return view('recovery._table', compact('cases'));
}
        return view('recovery.index', compact('cases', 'counts', 'types', 'view'));
    }

    public function show(RecoveryCase $case)
    {
        $case->load('order.customer', 'order.items.product', 'shipment.courier', 'actions');

        return view('recovery.show', ['case' => $case]);
    }

    public function act(Request $request, RecoveryCase $case)
    {
        $type = $request->validate([
            'type' => 'required|in:message,call,address,reschedule,retry,note,close',
        ])['type'];

        $case->loadMissing('order.store', 'shipment');
        $order = $case->order;
        $msg = 'Done.';

        switch ($type) {

            case 'message':
                $d = $request->validate([
                    'template' => 'required|in:failed_delivery_recovery,address_correction',
                    'channel'  => 'required|in:whatsapp,sms',
                ]);

                $tpl = DB::table('message_templates')->whereNull('store_id')
                    ->where('code', $d['template'])->where('channel', $d['channel'])->first()
                    ?? DB::table('message_templates')->whereNull('store_id')
                    ->where('code', $d['template'])->where('channel', 'whatsapp')->first();
                $token = Str::random(32);
                $case->update([
                    'recovery_token_hash'       => hash('sha256', $token),
                    'recovery_token_expires_at' => now()->addHours(48),
                ]);
                $link = url('/recover/' . $token);
                $body = str_replace(
                    ['{customer_name}', '{order_number}', '{store_name}', '{link}'],
                    [$order->customer_name, $order->order_number, $order->store->name,$link ],
                    $tpl->body ?? 'Hi {customer_name}, we could not deliver order {order_number}.'
                );

                DB::table('notifications')->insert([
                    'store_id' => $case->store_id, 'recipient_type' => 'customer',
                    'customer_id' => $case->customer_id, 'order_id' => $order->id,
                    'recovery_case_id' => $case->id, 'channel' => $d['channel'],
                    'template_code' => $d['template'], 'body' => $body, 'status' => 'sent',
                    'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);

                $this->log($case, 'message_sent', $d['channel'], 'success', ['template' => $d['template'], 'body' => $body]);
                $this->setStatus($case, 'awaiting_response', true);
                               $msg = 'Recovery message sent via ' . $d['channel'] . ' (simulated).';
                if (app()->environment('local')) {
                    $msg .= ' Customer link (local testing): ' . $link;
                }
                break;

            case 'call':
                $d = $request->validate([
                    'result' => 'required|in:success,no_answer,failed',
                    'note'   => 'nullable|string|max:300',
                ]);
                $this->log($case, 'call_made', 'call', $d['result'], ['note' => $d['note'] ?? null]);
                $this->setStatus($case, $d['result'] === 'success' ? 'contacted' : $case->status, true);
                $msg = 'Call logged.';
                break;

            case 'address':
                $d = $request->validate([
                    'corrected_address' => 'required|string|max:500',
                    'corrected_phone'   => 'nullable|string|max:30',
                ]);

                $case->update([
                    'corrected_address' => $d['corrected_address'],
                    'corrected_phone'   => $d['corrected_phone'] ?? null,
                ]);

                $orderUpdate = ['shipping_address_line1' => Str::limit($d['corrected_address'], 250, ''), 'is_address_complete' => 1];
                if (!empty($d['corrected_phone'])) {
                    $digits = preg_replace('/\D/', '', $d['corrected_phone']);
                    if (str_starts_with($digits, '0')) $digits = '92' . substr($digits, 1);
                    $orderUpdate['customer_phone'] = $d['corrected_phone'];
                    $orderUpdate['customer_phone_normalized'] = $digits;
                }
                $order->update($orderUpdate);

                $this->log($case, 'address_updated', 'system', 'success', $d);
                $this->setStatus($case, 'address_corrected', true);
                $msg = 'Address updated on the order.';
                break;

            case 'reschedule':
                $d = $request->validate(['reschedule_date' => 'required|date|after_or_equal:today']);
                $case->update(['reschedule_date' => $d['reschedule_date']]);
                $this->log($case, 'reschedule_requested', 'system', 'success', ['date' => $d['reschedule_date']]);
                $this->setStatus($case, 'reschedule_requested', true);
                $msg = 'Reschedule requested for ' . $d['reschedule_date'] . '.';
                break;

            case 'retry':
                $shipment = $case->shipment;
                $shipment->update(['status' => 'out_for_delivery', 'is_exception' => 0, 'last_event_at' => now()]);
                ShipmentEvent::create([
                    'shipment_id' => $shipment->id, 'status' => 'out_for_delivery', 'raw_status' => 'retry_booked',
                    'description' => 'Re-delivery attempt booked from recovery case ' . $case->case_number . '.',
                    'location' => $shipment->delivery_city, 'event_time' => now(),
                ]);
                $this->log($case, 'retry_booked', 'system', 'success', null);
                $this->setStatus($case, 'retry_booked', true);
                $msg = 'Retry booked. Shipment is active again.';
                break;

            case 'note':
                $d = $request->validate(['note' => 'required|string|max:500']);
                $this->log($case, 'note', 'system', 'success', ['note' => $d['note']]);
                $msg = 'Note added.';
                break;

            case 'close':
                $case->update(['status' => 'closed_unresolved', 'outcome' => 'unresolved', 'resolved_at' => now()]);
                $this->log($case, 'note', 'system', 'success', ['note' => 'Case closed as unresolved.']);
                $msg = 'Case closed as unresolved.';
                break;
        }

        return redirect()->route('rto-recovery.show', $case)->with('status', $msg);
    }

    private function log(RecoveryCase $case, string $type, string $channel, string $result, ?array $details): void
    {
        RecoveryAction::create([
            'recovery_case_id' => $case->id,
            'store_id'         => $case->store_id,
            'action_type'      => $type,
            'channel'          => $channel,
            'performed_by'     => auth()->id(),
            'result'           => $result,
            'details'          => $details,
        ]);
    }

    private function setStatus(RecoveryCase $case, string $status, bool $touchContact = false): void
    {
        $data = ['status' => $status];
        if ($touchContact && !$case->first_contact_at) $data['first_contact_at'] = now();
        $case->update($data);
    }
}