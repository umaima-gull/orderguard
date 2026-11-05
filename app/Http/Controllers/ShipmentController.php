<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Order;
use App\Models\RecoveryCase;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    private const STATUSES = ['booked','picked_up','in_transit','out_for_delivery','delivered','failed_attempt','on_hold','returning','returned','cancelled'];
    private const REASONS  = ['none','customer_unavailable','phone_unreachable','address_issue','customer_refused','customer_postponed','area_not_serviceable','courier_issue','other'];

    public function index(Request $request)
    {
        $store = \App\Support\CurrentStore::get();
        $sid = $store->id;

        $query = Shipment::with('order', 'courier')->where('store_id', $sid);
        if ($s = $request->query('status'))  $query->where('status', $s);
        if ($c = $request->query('courier')) $query->where('courier_id', $c);
        if ($request->query('exceptions'))   $query->where('is_exception', 1);
        if ($t = $request->query('q')) {
            $query->where(fn ($w) => $w->where('tracking_number', 'like', "%{$t}%")
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$t}%")
                    ->orWhere('customer_name', 'like', "%{$t}%")));
        }
        $shipments = $query->orderByDesc('created_at')->simplePaginate(10)->withQueryString();

        $counts = [
            'active'     => Shipment::where('store_id', $sid)->whereIn('status', ['booked','picked_up','in_transit','out_for_delivery'])->count(),
            'delivered'  => Shipment::where('store_id', $sid)->where('status', 'delivered')->count(),
            'exceptions' => Shipment::where('store_id', $sid)->where('is_exception', 1)->count(),
            'returned'   => Shipment::where('store_id', $sid)->whereIn('status', ['returning','returned'])->count(),
        ];

        // Approved orders that are not dispatched yet
        $ready = Order::where('store_id', $sid)->where('status', 'approved')
            ->whereDoesntHave('shipments')->get();

        $couriers = Courier::where('is_active', 1)->get();
// AJAX request ho to sirf table wala partial wapas bhejo
if ($request->ajax()) {
    return view('shipments._table', compact('shipments'));
}
        return view('shipments.index', compact('shipments', 'counts', 'ready', 'couriers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'courier_id' => 'required|exists:couriers,id',
        ]);

        $order = Order::findOrFail($data['order_id']);
        $courier = Courier::findOrFail($data['courier_id']);

        $account = DB::table('courier_accounts')
            ->where('store_id', $order->store_id)->where('courier_id', $courier->id)->value('id');

        $shipment = Shipment::create([
            'store_id'           => $order->store_id,
            'order_id'           => $order->id,
            'courier_id'         => $courier->id,
            'courier_account_id' => $account,
            'tracking_number'    => strtoupper(substr($courier->code, 0, 3)) . random_int(1000000, 9999999),
            'status'             => 'booked',
            'failure_reason'     => 'none',
            'cod_amount'         => $order->cod_amount,
            'delivery_city'      => $order->shipping_city,
            'dispatched_at'      => now(),
            'last_event_at'      => now(),
        ]);

        ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status'      => 'booked',
            'raw_status'  => 'booked',
            'description' => 'Shipment booked with ' . $courier->name . '.',
            'location'    => $order->shipping_city,
            'event_time'  => now(),
        ]);

        $order->update(['status' => 'dispatched', 'dispatched_at' => now()]);

        return redirect()->route('shipments.show', $shipment)
            ->with('status', 'Shipment booked for ' . $order->order_number . '.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load('order.customer', 'courier', 'events');
        $events = $shipment->events->sortByDesc('event_time');
        $case = RecoveryCase::where('shipment_id', $shipment->id)->first();

        $trackingUrl = $shipment->courier?->tracking_url_template
            ? str_replace('{tracking}', $shipment->tracking_number, $shipment->courier->tracking_url_template)
            : null;

        return view('shipments.show', [
            'shipment'    => $shipment,
            'events'      => $events,
            'case'        => $case,
            'trackingUrl' => $trackingUrl,
            'statuses'    => self::STATUSES,
            'reasons'     => array_filter(self::REASONS, fn ($r) => $r !== 'none'),
        ]);
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        $data = $request->validate([
            'status'         => 'required|in:' . implode(',', self::STATUSES),
            'failure_reason' => 'nullable|in:' . implode(',', self::REASONS),
            'description'    => 'nullable|string|max:300',
        ]);

        $status = $data['status'];
        $failed = $status === 'failed_attempt';
        $reason = $failed ? (($data['failure_reason'] ?? 'none') === 'none' ? 'other' : $data['failure_reason']) : null;

        $updates = [
            'status'        => $status,
            'is_exception'  => $failed ? 1 : 0,
            'last_event_at' => now(),
        ];
        if ($failed) {
            $updates['failure_reason'] = $reason;
            $updates['attempt_count']  = $shipment->attempt_count + 1;
        }
        if ($status === 'delivered') {
            $updates['delivered_at']  = now();
            $updates['cod_collected'] = $shipment->cod_amount;
        }
        if ($status === 'returned') {
            $updates['returned_at'] = now();
        }
        $shipment->update($updates);

        ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status'      => $status,
            'raw_status'  => $status,
            'description' => $data['description'] ?: ($failed
                ? 'Delivery attempt failed: ' . str_replace('_', ' ', $reason) . '.'
                : 'Status changed to ' . str_replace('_', ' ', $status) . '.'),
            'location'    => $shipment->delivery_city,
            'is_exception'=> $failed ? 1 : 0,
            'event_time'  => now(),
        ]);

        $order = $shipment->order;
                if ($failed) {
            \App\Services\Notifier::merchant(
                $shipment->order->store,
                'Delivery failed',
                'Shipment ' . $shipment->tracking_number . ' for ' . $shipment->order->order_number
                    . ' failed: ' . str_replace('_', ' ', $reason) . '. Open a recovery case.',
                $shipment->order_id
            );
        }
        if ($status === 'delivered') {
            $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        } elseif ($status === 'returned') {
            $order->update(['status' => 'returned']);
        }

        // Close the linked recovery case, if any
        $case = RecoveryCase::where('shipment_id', $shipment->id)->first();
        if ($case && in_array($status, ['delivered', 'returned'])) {
            $case->update([
                'status'            => $status,
                'outcome'           => $status === 'delivered' ? 'recovered' : 'returned',
                'revenue_recovered' => $status === 'delivered' ? $case->amount_at_risk : 0,
                'resolved_at'       => now(),
            ]);
        }

        if ($order->customer_id) {
            DB::statement('CALL sp_refresh_customer_stats(?)', [$order->customer_id]);
        }

        return redirect()->route('shipments.show', $shipment)->with('status', 'Shipment updated.');
    }

    public function openCase(Shipment $shipment)
    {
        $existing = RecoveryCase::where('shipment_id', $shipment->id)->first();
        if ($existing) {
            return redirect()->route('shipments.show', $shipment)
                ->with('status', 'A recovery case already exists (' . $existing->case_number . ').');
        }

        $order = $shipment->order;
        $type = $shipment->failure_reason === 'none' ? 'other' : $shipment->failure_reason;

        $case = RecoveryCase::create([
            'store_id'       => $shipment->store_id,
            'case_number'    => 'RC-' . str_pad((RecoveryCase::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT),
            'order_id'       => $order->id,
            'shipment_id'    => $shipment->id,
            'customer_id'    => $order->customer_id,
            'exception_type' => $type,
            'status'         => 'open',
            'priority'       => $type === 'address_issue' ? 'high' : 'normal',
            'amount_at_risk' => $shipment->cod_amount,
            'outcome'        => 'pending',
            'opened_at'      => now(),
            'due_at'         => now()->addDay(),
        ]);

        return redirect()->route('shipments.show', $shipment)
            ->with('status', 'Recovery case ' . $case->case_number . ' opened.');
    }
}