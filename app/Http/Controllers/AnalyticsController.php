<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RecoveryCase;
use App\Models\Store;
use App\Services\TrendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $sid = \App\Support\CurrentStore::get()->id;
        $days = in_array((int) $request->query('days'), [7, 30, 90]) ? (int) $request->query('days') : 30;
        $cost = max(0, (int) $request->query('cost', 350));   // assumed cost of one RTO (PKR)
        $from = now()->subDays($days - 1)->startOfDay();

        $pct = fn ($a, $b) => $b ? round($a / $b * 100, 1) : 0;

        // Orders
        $orders = Order::where('store_id', $sid)->where('placed_at', '>=', $from);
        $totalOrders = (clone $orders)->count();
        $risk = [
            'low'    => (clone $orders)->where('risk_level', 'low')->count(),
            'medium' => (clone $orders)->where('risk_level', 'medium')->count(),
            'high'   => (clone $orders)->where('risk_level', 'high')->count(),
            'none'   => (clone $orders)->whereNull('risk_level')->count(),
        ];

        // Verification
        $asked = (clone $orders)->whereIn('verification_status', ['pending', 'confirmed', 'rejected', 'no_response'])->count();
        $confirmed = (clone $orders)->where('verification_status', 'confirmed')->count();

        // Shipments
        $ship = DB::table('shipments')->where('store_id', $sid)->where('created_at', '>=', $from);
        $shipTotal = (clone $ship)->count();
        $delivered = (clone $ship)->where('status', 'delivered')->count();
        $returned  = (clone $ship)->whereIn('status', ['returning', 'returned'])->count();

        // Were flagged (medium/high) orders really the ones that failed?
        $flagged = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->where('shipments.store_id', $sid)->where('shipments.created_at', '>=', $from)
            ->whereIn('orders.risk_level', ['medium', 'high']);
        $flaggedShipped = (clone $flagged)->count();
        $flaggedFailed  = (clone $flagged)->where(fn ($q) => $q->where('shipments.failure_reason', '!=', 'none')
            ->orWhereIn('shipments.status', ['returning', 'returned']))->count();

        // Recovery
        $cases = RecoveryCase::where('store_id', $sid)->where('opened_at', '>=', $from);
        $casesTotal = (clone $cases)->count();
        $recovered  = (clone $cases)->where('outcome', 'recovered')->count();
        $revenue    = (float) (clone $cases)->where('outcome', 'recovered')->sum('revenue_recovered');
        $avgHours   = (clone $cases)->whereNotNull('resolved_at')
            ->selectRaw('avg(timestampdiff(HOUR, opened_at, resolved_at)) as h')->value('h');

        // Savings estimate
        $prevented = (clone $orders)->where('risk_level', 'high')
            ->whereIn('status', ['rejected', 'cancelled', 'on_hold'])->count();
        $savings = ($prevented + $recovered) * $cost;

        // Failure reasons
        $reasons = DB::table('shipments')->where('store_id', $sid)->where('created_at', '>=', $from)
            ->where('failure_reason', '!=', 'none')
            ->selectRaw('failure_reason, count(*) as c')->groupBy('failure_reason')
            ->orderByDesc('c')->get();

        // Courier comparison for the period
        $couriers = DB::table('shipments')->join('couriers', 'couriers.id', '=', 'shipments.courier_id')
            ->where('shipments.store_id', $sid)->where('shipments.created_at', '>=', $from)
            ->selectRaw('couriers.name, count(*) as total, sum(shipments.status = "delivered") as delivered,
                         sum(shipments.status in ("returning","returned")) as returned')
            ->groupBy('couriers.name')->orderByDesc('total')->get();

        return view('analytics.index', [
            'days' => $days, 'cost' => $cost,
            'chart' => TrendService::daily($sid, $days),
            'totalOrders' => $totalOrders, 'risk' => $risk,
            'verifyRate' => $pct($confirmed, $asked), 'asked' => $asked,
            'shipTotal' => $shipTotal,
            'deliveryRate' => $pct($delivered, $shipTotal), 'rtoRate' => $pct($returned, $shipTotal),
            'accuracy' => $pct($flaggedFailed, $flaggedShipped),
            'flaggedShipped' => $flaggedShipped, 'flaggedFailed' => $flaggedFailed,
            'casesTotal' => $casesTotal, 'recovered' => $recovered,
            'recoveryRate' => $pct($recovered, $casesTotal),
            'revenue' => $revenue, 'avgHours' => $avgHours !== null ? round($avgHours, 1) : null,
            'prevented' => $prevented, 'savings' => $savings,
            'reasons' => $reasons, 'couriers' => $couriers,
        ]);
    }
}