<?php

namespace App\Services;

use App\Models\Store;
use App\Models\WebhookEvent;
use Illuminate\Support\Str;

class WebhookProcessor
{
    private const ORDER_TOPICS = ['orders/create', 'orders/updated', 'order.created', 'order.updated'];

    public function handle(Store $store, string $source, string $topic, ?string $externalId, array $payload): WebhookEvent
    {
        $externalId = substr($externalId ?: (string) ($payload['id'] ?? Str::uuid()), 0, 150);

        $existing = WebhookEvent::where(compact('source') + ['event_type' => $topic, 'external_id' => $externalId])->first();
        if ($existing) {
            return $existing;   // duplicate delivery: nothing more to do
        }

        $event = WebhookEvent::create([
            'store_id'    => $store->id,
            'source'      => $source,
            'event_type'  => substr($topic, 0, 100),
            'external_id' => $externalId,
            'payload'     => $payload,
            'status'      => 'received',
        ]);

        return $this->process($event, $store);
    }

    public function process(WebhookEvent $event, Store $store): WebhookEvent
    {
        $event->attempts = min(255, $event->attempts + 1);

        try {
            if (in_array($event->event_type, self::ORDER_TOPICS, true)) {
                app(OrderImporter::class)->import($store, $event->source, $event->payload);
                $event->status = 'processed';
            } else {
                $event->status = 'ignored';
            }
            $event->error_message = null;
        } catch (\Throwable $e) {
            $event->status = 'failed';
            $event->error_message = Str::limit($e->getMessage(), 480, '');
        }

        $event->processed_at = now();
        $event->save();

        return $event;
    }
}