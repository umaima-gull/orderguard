<?php

namespace App\Services;

use App\Models\FraudRule;
use App\Models\Order;
use App\Models\OrderRiskScore;
use App\Models\RiskFactor;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class RiskScoringService
{
    public function evaluate(Order $order, string $version = 'v1.0'): OrderRiskScore
    {
        $order->loadMissing('customer', 'store');
        $store = $order->store;

        // Store's own rules first, otherwise system default rules
        $rules = FraudRule::where('store_id', $store->id)->where('is_active', 1)->orderBy('priority')->get();
        if ($rules->isEmpty()) {
            $rules = FraudRule::whereNull('store_id')->where('is_active', 1)->orderBy('priority')->get();
        }

        $factors = [];
        $total = 0;

        foreach ($rules as $rule) {
            $reason = $this->check($rule, $order);
            if ($reason !== null) {
                $factors[] = [$rule, $reason];
                $total += $rule->score_impact;
            }
        }

        $score = max(0, min(100, $total));

        $level = $score <= $store->low_risk_max ? 'low'
               : ($score <= $store->medium_risk_max ? 'medium' : 'high');
        $action = ['low' => 'approve', 'medium' => 'verify', 'high' => 'review'][$level];

        return DB::transaction(function () use ($order, $store, $score, $level, $action, $version, $factors) {
            OrderRiskScore::where('order_id', $order->id)->update(['is_latest' => 0]);

            $risk = OrderRiskScore::create([
                'order_id' => $order->id,
                'store_id' => $store->id,
                'score' => $score,
                'risk_level' => $level,
                'recommended_action' => $action,
                'evaluated_by' => 'system',
                'rules_version' => $version,
                'is_latest' => 1,
            ]);

            foreach ($factors as [$rule, $reason]) {
                RiskFactor::create([
                    'risk_score_id' => $risk->id,
                    'rule_id' => $rule->id,
                    'factor_code' => $rule->code,
                    'label' => $rule->name,
                    'description' => $reason,
                    'score_impact' => $rule->score_impact,
                ]);
            }

            $order->update(['risk_score' => $score, 'risk_level' => $level]);
            // Auto-hold: only for orders still waiting for a merchant decision
if ($store->auto_hold_high_risk && $level === 'high' && in_array($order->status, ['pending', 'under_review'])) {
    $order->update(['status' => 'on_hold', 'merchant_decision' => 'held']);
}

            return $risk;
        });
    }

    /** Returns an explanation text if the rule applies, otherwise null. */
    private function check(FraudRule $rule, Order $order): ?string
    {
        $p = $rule->parameters ?? [];
        $c = $order->customer;

        switch ($rule->rule_type) {

            case 'blacklisted_customer':
                return ($c && $c->is_blacklisted) ? 'Customer is blacklisted by the merchant.' : null;

            case 'repeated_unconfirmed':
                if (!$c) return null;
                $days = $p['window_days'] ?? 30;
                $n = Order::where('customer_id', $c->id)->where('id', '!=', $order->id)
                    ->where('verification_status', 'no_response')
                    ->where('placed_at', '>=', now()->subDays($days))->count();
                return $n >= ($p['min_count'] ?? 2) ? "{$n} orders were left unconfirmed in the last {$days} days." : null;

            case 'recent_failed_deliveries':
                if (!$c) return null;
                $days = $p['window_days'] ?? 60;
                $n = Shipment::whereHas('order', fn ($q) => $q->where('customer_id', $c->id))
                    ->where('failure_reason', '!=', 'none')
                    ->where('created_at', '>=', now()->subDays($days))->count();
                return $n >= ($p['min_count'] ?? 2) ? "{$n} deliveries failed in the last {$days} days." : null;

            case 'incomplete_contact':
                $digits = preg_replace('/\D/', '', $order->customer_phone_normalized ?: $order->customer_phone ?: '');
                if (strlen($digits) < ($p['min_phone_digits'] ?? 10)) return 'Phone number is missing or too short.';
                if (!$order->is_address_complete || !$order->shipping_address_line1) return 'Delivery address is incomplete.';
                return null;

            case 'duplicate_order':
                $placed = $order->placed_at ?? now();
                $from = $placed->copy()->subHours($p['window_hours'] ?? 24);
                $dup = Order::where('store_id', $order->store_id)->where('id', '!=', $order->id)
                    ->where('placed_at', '>=', $from)->where('placed_at', '<=', $placed)
                    ->where(function ($q) use ($order) {
                        if ($order->customer_phone_normalized) $q->orWhere('customer_phone_normalized', $order->customer_phone_normalized);
                        if ($order->ip_address) $q->orWhere('ip_address', $order->ip_address);
                    })->exists();
                return $dup ? 'Another order came from the same phone or IP within a short time.' : null;

            case 'verified_history':
                if (!$c) return null;
                $delivered = Order::where('customer_id', $c->id)->where('id', '!=', $order->id)->where('status', 'delivered')->count();
                $returned  = Order::where('customer_id', $c->id)->where('id', '!=', $order->id)->where('status', 'returned')->count();
                return ($delivered >= ($p['min_delivered'] ?? 2) && $returned === 0)
                    ? "Customer has {$delivered} successfully delivered orders." : null;

            case 'high_order_value':
                $avg = Order::where('store_id', $order->store_id)->where('id', '!=', $order->id)->avg('total_amount');
                return ($avg && $order->total_amount > $avg * ($p['multiplier'] ?? 3))
                    ? 'Order value is much higher than the store average.' : null;
        }

        return null;
    }
}