<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\WebhookProcessor;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function receive(Request $request, string $source, Store $store, WebhookProcessor $processor)
    {
        abort_unless(in_array($source, ['shopify', 'woocommerce'], true), 404);
        abort_unless($store->webhook_secret, 403, 'Webhook secret is not set.');

        $raw = $request->getContent();
        $sigHeader = $source === 'shopify' ? 'X-Shopify-Hmac-Sha256' : 'X-WC-Webhook-Signature';
        $expected = base64_encode(hash_hmac('sha256', $raw, $store->webhook_secret, true));

        if (!hash_equals($expected, (string) $request->header($sigHeader))) {
            abort(401, 'Invalid signature.');
        }

        $payload = json_decode($raw, true);
        abort_unless(is_array($payload), 400, 'Invalid JSON.');

        $topic = $source === 'shopify'
            ? (string) $request->header('X-Shopify-Topic')
            : (string) $request->header('X-WC-Webhook-Topic');
        $extId = $source === 'shopify'
            ? $request->header('X-Shopify-Webhook-Id')
            : $request->header('X-WC-Webhook-Delivery-ID');

        $event = $processor->handle($store, $source, $topic ?: 'unknown', $extId, $payload);

        return response()->json(['status' => $event->status]);
    }
}