<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RecoveryCase;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function download(Request $request, string $type)
    {
        abort_unless(in_array($type, ['orders', 'customers', 'shipments', 'recovery'], true), 404);

        $filename = 'orderguard-' . $type . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($type, $request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel reads it correctly

            match ($type) {
                'orders'    => $this->orders($out),
                'customers' => $this->customers($out, $request),
                'shipments' => $this->shipments($out),
                'recovery'  => $this->recovery($out),
            };

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function orders($out): void
    {
        $this->row($out, ['Order', 'Placed at', 'Customer', 'Phone', 'City', 'Product', 'Payment', 'Total', 'Risk score', 'Risk level', 'Status', 'Verification', 'Decision']);

        Order::with('items')->chunkById(200, function ($orders) use ($out) {
            foreach ($orders as $o) {
                $this->row($out, [
                    $o->order_number, $o->placed_at?->format('Y-m-d H:i'), $o->customer_name, $o->customer_phone,
                    $o->shipping_city, $o->items->pluck('name')->implode(', '), $o->payment_method,
                    $o->total_amount, $o->risk_score, $o->risk_level, $o->status,
                    $o->verification_status, $o->merchant_decision,
                ]);
            }
        });
    }

    private function customers($out, Request $request): void
    {
        $this->row($out, ['Name', 'Phone', 'City', 'Total orders', 'Delivered', 'Returned (RTO)', 'Failed deliveries', 'Unconfirmed', 'Success rate %', 'Total spent', 'Blacklisted', 'Reason']);

        $query = Customer::query();

        if ($t = $request->query('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$t}%")
                ->orWhere('phone', 'like', "%{$t}%")
                ->orWhere('city', 'like', "%{$t}%"));
        }

        switch ($request->query('filter')) {
            case 'blacklisted':
                $query->where('is_blacklisted', 1);
                break;
            case 'problems':
                $query->where(fn ($w) => $w->where('failed_deliveries', '>', 0)->orWhere('rto_orders', '>', 0)
                    ->orWhere('unconfirmed_orders', '>', 0));
                break;
            case 'trusted':
                $query->where('delivered_orders', '>=', 2)->where('rto_orders', 0)->where('is_blacklisted', 0);
                break;
        }

        $query->chunkById(200, function ($customers) use ($out) {
            foreach ($customers as $c) {
                $this->row($out, [
                    $c->name, $c->phone, $c->city, $c->total_orders, $c->delivered_orders, $c->rto_orders,
                    $c->failed_deliveries, $c->unconfirmed_orders,
                    $c->total_orders ? round($c->delivered_orders / $c->total_orders * 100, 1) : 0,
                    $c->total_spent, $c->is_blacklisted ? 'Yes' : 'No', $c->blacklist_reason,
                ]);
            }
        });
    }

    private function shipments($out): void
    {
        $this->row($out, ['Tracking', 'Order', 'Customer', 'Courier', 'City', 'Status', 'Failure reason', 'Attempts', 'COD amount', 'Dispatched', 'Delivered']);

        Shipment::with('order', 'courier')->chunkById(200, function ($shipments) use ($out) {
            foreach ($shipments as $s) {
                $this->row($out, [
                    $s->tracking_number, $s->order?->order_number, $s->order?->customer_name, $s->courier?->name,
                    $s->delivery_city, $s->status, $s->failure_reason, $s->attempt_count, $s->cod_amount,
                    $s->dispatched_at?->format('Y-m-d H:i'), $s->delivered_at?->format('Y-m-d H:i'),
                ]);
            }
        });
    }

    private function recovery($out): void
    {
        $this->row($out, ['Case', 'Order', 'Customer', 'Issue', 'Status', 'Outcome', 'Amount at risk', 'Revenue recovered', 'Opened', 'Resolved']);

        RecoveryCase::with('order')->chunkById(200, function ($cases) use ($out) {
            foreach ($cases as $c) {
                $this->row($out, [
                    $c->case_number, $c->order?->order_number, $c->order?->customer_name, $c->exception_type,
                    $c->status, $c->outcome, $c->amount_at_risk, $c->revenue_recovered,
                    $c->opened_at?->format('Y-m-d H:i'), $c->resolved_at?->format('Y-m-d H:i'),
                ]);
            }
        });
    }

    /** Writes one CSV row and blocks spreadsheet formula injection. */
    private function row($out, array $cells): void
    {
        fputcsv($out, array_map(function ($v) {
            if (is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true)) {
                return "'" . $v;
            }
            return $v;
        }, $cells));
    }
}