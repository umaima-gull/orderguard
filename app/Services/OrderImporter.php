<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderImporter
{
    public function import(Store $store, string $source, array $payload): Order
    {
        $d = $source === 'woocommerce' ? $this->fromWoo($payload) : $this->fromShopify($payload);

        $order = DB::transaction(function () use ($store, $d, $source) {
            $customer = $this->customer($store, $d);

            $order = Order::firstOrNew(['store_id' => $store->id, 'external_order_id' => $d['external_id']]);
            $isNew = !$order->exists;

            $order->fill([
                'customer_id'               => $customer->id,
                'order_number'              => $d['number'],
                'source'                    => $source,
                'payment_method'            => $d['cod'] ? 'cod' : 'prepaid',
                'payment_status'            => $d['cod'] ? 'pending' : 'paid',
                'subtotal'                  => $d['subtotal'],
                'discount_amount'           => $d['discount'],
                'total_amount'              => $d['total'],
                'cod_amount'                => $d['cod'] ? $d['total'] : 0,
                'currency'                  => $d['currency'] ?: $store->currency,
                'customer_name'             => $d['name'],
                'customer_phone'            => $d['phone'],
                'customer_phone_normalized' => $d['phone_norm'],
                'customer_email'            => $d['email'],
                'shipping_address_line1'    => $d['address1'],
                'shipping_address_line2'    => $d['address2'],
                'shipping_city'             => $d['city'],
                'shipping_state'            => $d['state'],
                'shipping_postal_code'      => $d['zip'],
                'shipping_country_code'     => $d['country'],
                'is_address_complete'       => $d['address1'] && $d['city'] ? 1 : 0,
                'ip_address'                => $d['ip'],
                'placed_at'                 => $d['placed_at'],
            ]);
            if ($isNew) {
                $order->status = 'pending';
            }
            $order->save();

            if ($isNew) {
                foreach ($d['items'] as $i) {
                    $product = $i['sku']
                        ? DB::table('products')->where('store_id', $store->id)->where('sku', $i['sku'])->first()
                        : null;
                    $order->items()->create([
                        'product_id'  => $product->id ?? null,
                        'sku'         => $i['sku'],
                        'name'        => $i['name'],
                        'quantity'    => $i['qty'],
                        'unit_price'  => $i['price'],
                        'total_price' => $i['price'] * $i['qty'],
                    ]);
                }
            }

            return $order;
        });

        DB::statement('CALL sp_refresh_customer_stats(?)', [$order->customer_id]);
               $risk = app(RiskScoringService::class)->evaluate($order->fresh());

        if ($risk->risk_level === 'high') {
            Notifier::merchant(
                $store,
                'High-risk order received',
                $order->order_number . ' from ' . $order->customer_name . ' scored ' . $risk->score . '/100. Review it before dispatch.',
                $order->id
            );
        }

        return $order;
    }

    private function customer(Store $store, array $d): Customer
    {
        $c = null;
        if ($d['customer_ext']) {
            $c = Customer::where('store_id', $store->id)->where('external_id', $d['customer_ext'])->first();
        }
        if (!$c && $d['phone_norm']) {
            $c = Customer::where('store_id', $store->id)->where('phone_normalized', $d['phone_norm'])->first();
        }
        if (!$c) {
            $c = new Customer(['store_id' => $store->id, 'external_id' => $d['customer_ext']]);
        }

        $c->name = $c->name ?: $d['name'];
        $c->phone = $c->phone ?: $d['phone'];
        $c->phone_normalized = $c->phone_normalized ?: $d['phone_norm'];
        $c->email = $c->email ?: $d['email'];
        $c->address_line1 = $d['address1'] ?: $c->address_line1;
        $c->city = $d['city'] ?: $c->city;
        $c->country_code = $c->country_code ?: $d['country'];
        $c->save();

        return $c;
    }

    private function fromShopify(array $p): array
    {
        $ship = $p['shipping_address'] ?? [];
        $cust = $p['customer'] ?? [];
        $name = trim($ship['name'] ?? (($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''))) ?: 'Unknown customer';
        $phone = $ship['phone'] ?? $p['phone'] ?? $cust['phone'] ?? null;

        $gateways = array_map('strtolower', (array) ($p['payment_gateway_names'] ?? [$p['gateway'] ?? '']));
        $cod = collect($gateways)->contains(fn ($g) => str_contains($g, 'cash on delivery') || $g === 'cod');

        return [
            'external_id' => (string) $p['id'],
            'number'      => $p['name'] ?? ('#' . $p['id']),
            'cod'         => $cod,
            'subtotal'    => (float) ($p['subtotal_price'] ?? 0),
            'discount'    => (float) ($p['total_discounts'] ?? 0),
            'total'       => (float) ($p['total_price'] ?? 0),
            'currency'    => $p['currency'] ?? null,
            'customer_ext'=> isset($cust['id']) ? (string) $cust['id'] : null,
            'name'        => $name,
            'phone'       => $phone ? substr($phone, 0, 30) : null,
            'phone_norm'  => $this->normalize($phone),
            'email'       => $p['email'] ?? $cust['email'] ?? null,
            'address1'    => $ship['address1'] ?? null,
            'address2'    => $ship['address2'] ?? null,
            'city'        => $ship['city'] ?? null,
            'state'       => $ship['province'] ?? null,
            'zip'         => $ship['zip'] ?? null,
            'country'     => $ship['country_code'] ?? null,
            'ip'          => isset($p['browser_ip']) ? substr($p['browser_ip'], 0, 45) : null,
            'placed_at'   => isset($p['created_at']) ? Carbon::parse($p['created_at']) : now(),
            'items'       => collect($p['line_items'] ?? [])->map(fn ($i) => [
                'sku' => $i['sku'] ?? null, 'name' => $i['title'] ?? $i['name'] ?? 'Item',
                'qty' => (int) ($i['quantity'] ?? 1), 'price' => (float) ($i['price'] ?? 0),
            ])->all(),
        ];
    }

    private function fromWoo(array $p): array
    {
        $bill = $p['billing'] ?? [];
        $ship = $p['shipping'] ?? [];
        $name = trim(($ship['first_name'] ?? $bill['first_name'] ?? '') . ' ' . ($ship['last_name'] ?? $bill['last_name'] ?? '')) ?: 'Unknown customer';
        $phone = $bill['phone'] ?? $ship['phone'] ?? null;
        $total = (float) ($p['total'] ?? 0);
        $discount = (float) ($p['discount_total'] ?? 0);

        return [
            'external_id' => (string) $p['id'],
            'number'      => '#' . ($p['number'] ?? $p['id']),
            'cod'         => ($p['payment_method'] ?? '') === 'cod',
            'subtotal'    => $total + $discount,
            'discount'    => $discount,
            'total'       => $total,
            'currency'    => $p['currency'] ?? null,
            'customer_ext'=> !empty($p['customer_id']) ? (string) $p['customer_id'] : null,
            'name'        => $name,
            'phone'       => $phone ? substr($phone, 0, 30) : null,
            'phone_norm'  => $this->normalize($phone),
            'email'       => $bill['email'] ?? null,
            'address1'    => $ship['address_1'] ?? $bill['address_1'] ?? null,
            'address2'    => $ship['address_2'] ?? $bill['address_2'] ?? null,
            'city'        => $ship['city'] ?? $bill['city'] ?? null,
            'state'       => $ship['state'] ?? $bill['state'] ?? null,
            'zip'         => $ship['postcode'] ?? $bill['postcode'] ?? null,
            'country'     => $ship['country'] ?? $bill['country'] ?? null,
            'ip'          => isset($p['customer_ip_address']) ? substr($p['customer_ip_address'], 0, 45) : null,
            'placed_at'   => isset($p['date_created']) ? Carbon::parse($p['date_created']) : now(),
            'items'       => collect($p['line_items'] ?? [])->map(fn ($i) => [
                'sku' => $i['sku'] ?? null, 'name' => $i['name'] ?? 'Item',
                'qty' => (int) ($i['quantity'] ?? 1), 'price' => (float) ($i['price'] ?? 0),
            ])->all(),
        ];
    }

    private function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');
        if ($digits === '') return null;
        if (str_starts_with($digits, '0')) $digits = '92' . substr($digits, 1);
        return substr($digits, 0, 20);
    }
}