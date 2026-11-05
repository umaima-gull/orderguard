<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\WebhookEvent;
use App\Services\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $store = \App\Support\CurrentStore::get();

        if (!$store->webhook_secret) {
            $store->update(['webhook_secret' => Str::random(40)]);
        }

        $query = WebhookEvent::where('store_id', $store->id);
        if ($s = $request->query('status')) $query->where('status', $s);
        $events = $query->orderByDesc('id')->limit(25)->get();

        $counts = WebhookEvent::where('store_id', $store->id)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $urls = [
            'shopify'     => url('/webhooks/shopify/' . $store->id),
            'woocommerce' => url('/webhooks/woocommerce/' . $store->id),
        ];

        return view('integrations.index', [
            'store' => $store, 'events' => $events, 'counts' => $counts, 'urls' => $urls,
            'hasToken'  => (bool) $store->access_token,
            'hasSecret' => (bool) $store->api_secret,
        ]);
    }

    public function update(Request $request)
    {
        $store = \App\Support\CurrentStore::get();

        $data = $request->validate([
            'platform' => 'required|in:shopify,woocommerce,manual,custom_api',
            'domain'   => ['nullable', 'string', 'max:190',
                Rule::unique('stores')->where('platform', $request->input('platform'))->ignore($store->id)],
            'external_store_id' => 'nullable|string|max:100',
            'api_key'      => 'nullable|string|max:255',
            'access_token' => 'nullable|string|max:2000',
            'api_secret'   => 'nullable|string|max:2000',
        ]);

        $update = [
            'platform' => $data['platform'],
            'domain' => $data['domain'] ?: null,
            'external_store_id' => $data['external_store_id'] ?: null,
        ];
        if (!empty($data['api_key']))      $update['api_key'] = $data['api_key'];
        if (!empty($data['access_token'])) $update['access_token'] = Crypt::encryptString($data['access_token']);
        if (!empty($data['api_secret']))   $update['api_secret'] = Crypt::encryptString($data['api_secret']);
        $update['status'] = !empty($update['domain']) ? 'connected' : 'pending';

        $store->update($update);

        return redirect()->route('integrations')->with('status', 'Connection saved. Credentials are stored encrypted.');
    }

    public function regenerateSecret()
    {
        \App\Support\CurrentStore::get()->update(['webhook_secret' => Str::random(40)]);

        return redirect()->route('integrations')
            ->with('status', 'New webhook secret created. Update it in Shopify/WooCommerce too.');
    }

    public function sync()
    {
        $store = \App\Support\CurrentStore::get();
        $store->update(['last_synced_at' => now()]);   // simulated: a real API sync will go here

        return redirect()->route('integrations')->with('status', 'Sync completed (simulated).');
    }

    public function disconnect()
    {
        \App\Support\CurrentStore::get()->update(['status' => 'disconnected']);

        return redirect()->route('integrations')->with('status', 'Store disconnected. Webhooks are still logged.');
    }

    public function test(Request $request, WebhookProcessor $processor)
    {
        $store = \App\Support\CurrentStore::get();
        $source = $request->validate(['source' => 'required|in:shopify,woocommerce'])['source'];

        $product = DB::table('products')->where('store_id', $store->id)->inRandomOrder()->first();
        $n = random_int(1000, 9999);
        $phone = '+92 3' . random_int(10, 49) . ' ' . random_int(1000000, 9999999);
        $sku = $product->sku ?? null;
        $title = $product->name ?? 'Test product';
        $price = (float) ($product->price ?? 1500);

        if ($source === 'shopify') {
            $payload = [
                'id' => 880000 + $n, 'name' => '#T' . $n, 'created_at' => now()->toIso8601String(),
                'currency' => 'PKR', 'subtotal_price' => $price, 'total_price' => $price, 'total_discounts' => 0,
                'payment_gateway_names' => ['Cash on Delivery (COD)'], 'browser_ip' => '203.0.113.' . random_int(2, 250),
                'customer' => ['id' => 770000 + $n, 'first_name' => 'Test', 'last_name' => 'Customer ' . $n],
                'shipping_address' => ['name' => 'Test Customer ' . $n, 'phone' => $phone, 'address1' => 'House ' . $n . ', Main Road',
                    'city' => 'Lahore', 'province' => 'Punjab', 'zip' => '54000', 'country_code' => 'PK'],
                'line_items' => [['sku' => $sku, 'title' => $title, 'quantity' => 1, 'price' => $price]],
            ];
            $topic = 'orders/create';
        } else {
            $payload = [
                'id' => 660000 + $n, 'number' => 'T' . $n, 'date_created' => now()->toIso8601String(), 'currency' => 'PKR',
                'total' => $price, 'discount_total' => 0, 'payment_method' => 'cod', 'customer_ip_address' => '203.0.113.' . random_int(2, 250),
                'billing' => ['first_name' => 'Test', 'last_name' => 'Customer ' . $n, 'phone' => $phone, 'email' => "test{$n}@example.com"],
                'shipping' => ['first_name' => 'Test', 'last_name' => 'Customer ' . $n, 'address_1' => 'Street ' . $n . ', Gulberg',
                    'city' => 'Karachi', 'state' => 'Sindh', 'postcode' => '75500', 'country' => 'PK'],
                'line_items' => [['sku' => $sku, 'name' => $title, 'quantity' => 1, 'price' => $price]],
            ];
            $topic = 'order.created';
        }

        $event = $processor->handle($store, $source, $topic, 'test-' . Str::random(10), $payload);

        return redirect()->route('integrations')
            ->with('status', 'Test ' . $source . ' order ' . $event->status . '. Open Orders to see it.');
    }

    public function retry(WebhookEvent $event, WebhookProcessor $processor)
    {
        $store = \App\Support\CurrentStore::get();
        abort_unless($event->store_id === $store->id, 403);

        $event = $processor->process($event, $store);

        return redirect()->route('integrations')->with('status', 'Event retried: ' . $event->status . '.');
    }
}