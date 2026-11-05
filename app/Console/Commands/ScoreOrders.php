<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\RiskScoringService;
use Illuminate\Console\Command;

class ScoreOrders extends Command
{
    protected $signature = 'orders:score {--store=}';
    protected $description = 'Orders ka risk score nikalta hai';

    public function handle(RiskScoringService $svc)
    {
        $q = Order::query();
        if ($this->option('store')) {
            $q->where('store_id', $this->option('store'));
        }

        foreach ($q->get() as $order) {
            $r = $svc->evaluate($order);
            $this->line("{$order->order_number}: {$r->score}/100 ({$r->risk_level}) - {$r->factors()->count()} wajohat");
        }
    }
}