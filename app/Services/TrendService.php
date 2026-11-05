<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RecoveryCase;

class TrendService
{
    /** Daily orders and recovered-case counts for the last $days days. */
    public static function daily(int $storeId, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $orders = Order::where('store_id', $storeId)->where('placed_at', '>=', $start)
            ->selectRaw('DATE(placed_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd');

        $recovered = RecoveryCase::where('store_id', $storeId)->where('outcome', 'recovered')
            ->where('resolved_at', '>=', $start)
            ->selectRaw('DATE(resolved_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd');

        $labels = $o = $r = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $o[] = (int) ($orders[$key] ?? 0);
            $r[] = (int) ($recovered[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'orders' => $o,
            'recovered' => $r,
            'orders_total' => array_sum($o),
            'recovered_total' => array_sum($r),
            'days' => $days,
        ];
    }
}