<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Order;
use App\Models\RecoveryCase;
use App\Models\Store;
use App\Services\TrendService;

class DashboardController extends Controller
{
    public function index()
    {
        // Abhi login nahi hai, isliye pehla store. Login ke baad user ka store aayega.
        $store = \App\Support\CurrentStore::get();
        $sid   = $store->id;

        /* ---------- KPIs ---------- */
        $totalOrders = Order::where('store_id', $sid)->count();
        $highRisk    = Order::where('store_id', $sid)->where('risk_level', 'high')->count();

        $openStatuses = ['delivered', 'returned', 'closed_unresolved'];
        $openCases = RecoveryCase::where('store_id', $sid)->whereNotIn('status', $openStatuses)->count();
        $dueToday  = RecoveryCase::where('store_id', $sid)->whereNotIn('status', $openStatuses)
                        ->whereDate('due_at', '<=', today())->count();

        $recoveredRevenue = (float) RecoveryCase::where('store_id', $sid)
                        ->where('outcome', 'recovered')->sum('revenue_recovered');
        $recoveredCount   = RecoveryCase::where('store_id', $sid)->where('outcome', 'recovered')->count();

        $revenueText = $recoveredRevenue >= 100000
            ? '₨ ' . round($recoveredRevenue / 100000, 1) . 'L'
            : '₨ ' . number_format($recoveredRevenue);

        $kpis = [
            ['label' => 'Orders analysed',      'icon' => 'i-pkg',          'value' => number_format($totalOrders), 'change' => 'All time',               'note' => 'in your store'],
            ['label' => 'High-risk orders',     'icon' => 'i-shield-alert', 'value' => number_format($highRisk),    'change' => ($totalOrders ? round($highRisk / $totalOrders * 100, 1) : 0) . '%', 'note' => 'of all orders'],
            ['label' => 'RTO cases to recover', 'icon' => 'i-undo',         'value' => number_format($openCases),   'change' => $dueToday . ' due today', 'note' => 'follow-up queue'],
            ['label' => 'Revenue recovered',    'icon' => 'i-trend',        'value' => $revenueText,                'change' => $recoveredCount . ' cases', 'note' => 'recovered'],
        ];

        /* ---------- Orders table ---------- */
        $orders = Order::with('items.product')
            ->where('store_id', $sid)
            ->orderByDesc('placed_at')
            ->take(5)->get()
            ->map(function ($o) {
                $words = array_filter(explode(' ', trim($o->customer_name)));
                $initials = strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
                $first = $o->items->first();

                return [
                    'db_id'    => $o->id,
                    'id'       => $o->order_number,
                    'customer' => $o->customer_name,
                    'initials' => $initials,
                    'avatar'   => strtolower(reset($words)) . '.jpg',   // ali.jpg, sara.jpg ...
                    'product'  => $first->name ?? '-',
                    'image'    => $first?->product?->image_url,
                    'level'    => $o->risk_level,
                    'score'    => $o->risk_score,                        // null = abhi score nahi hua
                    'value'    => $o->total_amount,
                ];
            })->all();

        /* ---------- Risk breakdown ---------- */
        $low    = Order::where('store_id', $sid)->where('risk_level', 'low')->count();
        $medium = Order::where('store_id', $sid)->where('risk_level', 'medium')->count();
        $high   = $highRisk;
        $unscored = max(0, $totalOrders - $low - $medium - $high);

        $segments = [
            ['Low risk',  $low,      '#2fbf4a'],
            ['Medium',    $medium,   '#f5a524'],
            ['High risk', $high,     '#e8112d'],
            ['Unscored',  $unscored, '#b04be0'],
        ];

        $riskBreakdown = [];
        $parts = [];
        $start = 0;
        foreach ($segments as [$label, $count, $color]) {
            $pct = $totalOrders ? $count / $totalOrders * 100 : 0;
            $end = $start + $pct;
            $parts[] = "{$color} {$start}% {$end}%";
            $start = $end;
            $riskBreakdown[] = ['label' => $label, 'percent' => round($pct)];
        }
        $donut = $totalOrders ? 'conic-gradient(' . implode(',', $parts) . ')' : '#e8eeea';

        /* ---------- RTO recovery queue ---------- */
        $exceptionMap = [
            'address_issue'        => ['i-pin',     'Address correction'],
            'phone_unreachable'    => ['i-phone',   'Phone unreachable'],
            'customer_unavailable' => ['i-phone',   'Customer unavailable'],
            'customer_postponed'   => ['i-refresh', 'Delivery reschedule'],
            'customer_refused'     => ['i-refresh', 'Customer refused'],
        ];

        $recoveries = RecoveryCase::with('order.items.product')
            ->where('store_id', $sid)
            ->whereNotIn('status', $openStatuses)
            ->orderBy('due_at')->take(4)->get()
            ->map(function ($c) use ($exceptionMap) {
                [$icon, $title] = $exceptionMap[$c->exception_type] ?? ['i-refresh', 'Delivery exception'];
                $due = $c->due_at;

                return [
                    'icon'   => $icon,
                    'image'  => $c->order?->items->first()?->product?->image_url,
                    'title'  => $title,
                    'order'  => $c->order?->order_number,
                    'status' => ucfirst(str_replace('_', ' ', $c->status)),
                    'amount' => $c->amount_at_risk,
                    'due'    => $due ? ($due->isToday() ? 'Due today' : ($due->isPast() ? 'Overdue' : $due->format('M j'))) : '-',
                ];
            })->all();

        /* ---------- Courier scorecard ---------- */
        $couriers = Courier::query()
            ->whereIn('id', function ($q) use ($sid) {
                $q->select('courier_id')->from('courier_accounts')->where('store_id', $sid);
            })
            ->selectRaw('couriers.name,
                (select count(*) from shipments s where s.courier_id = couriers.id and s.store_id = ?) as total,
                (select count(*) from shipments s where s.courier_id = couriers.id and s.store_id = ? and s.status = "delivered") as delivered',
                [$sid, $sid])
            ->get()
            ->map(fn ($c) => [
                'name' => $c->name,
                'rate' => $c->total ? round($c->delivered / $c->total * 100) : 0,
            ])->all();

        /* ---------- Latest activity ---------- */
        $activities = Order::where('store_id', $sid)
            ->orderByDesc('placed_at')->take(3)->get()
            ->map(function ($o) {
                $high = $o->risk_level === 'high';
                return [
                    'bold'  => $high ? 'High risk detected' : 'Order ' . $o->order_number,
                    'text'  => $high ? 'for order ' . $o->order_number . '.' : 'received from ' . $o->customer_name . '.',
                    'time'  => $o->placed_at->diffForHumans(),
                    'color' => $high ? '#e2b45d' : null,
                ];
            })->all();

        $flaggedCount = Order::where('store_id', $sid)
            ->where(fn ($q) => $q->where('risk_level', 'high')->orWhere('status', 'under_review'))->count();
$days = in_array((int) request('days'), [7, 30, 90]) ? (int) request('days') : 7;
$chart = TrendService::daily($sid, $days);
        return view('dashboard', [
            'kpis'            => $kpis,
            'orders'          => $orders,
            'recoveries'      => $recoveries,
            'couriers'        => $couriers,
            'activities'      => $activities,
            'riskBreakdown'   => $riskBreakdown,
            'donut'           => $donut,
            'totalOrders'     => $totalOrders,
            'recoveredOrders' => $recoveredCount,
            'flaggedCount'    => $flaggedCount,
            'rtoDueCount'     => $dueToday,
            'chart' => $chart, 
            'days' => $days
        ]);
    }
}