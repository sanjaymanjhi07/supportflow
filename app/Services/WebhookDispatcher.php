<?php

namespace App\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\Tenant;
use App\Models\Webhook;

class WebhookDispatcher
{
    /**
     * Fan out an event to every active webhook subscribed to it for the
     * given tenant. Each delivery is queued independently so a slow or
     * failing endpoint never blocks the others.
     */
    public function dispatch(Tenant $tenant, string $event, array $payload): void
    {
        Webhook::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $webhook) => $webhook->subscribesTo($event))
            ->each(function (Webhook $webhook) use ($event, $payload) {
                DispatchWebhookJob::dispatch($webhook, $event, [
                    'event' => $event,
                    'data' => $payload,
                    'sent_at' => now()->toIso8601String(),
                ]);
            });
    }

    /**
     * HMAC-SHA256 sign a payload using the webhook's per-endpoint secret,
     * falling back to the global signing secret. Receivers verify this
     * against the X-SupportFlow-Signature header.
     */
    public function sign(Webhook $webhook, string $rawBody): string
    {
        $secret = $webhook->secret ?: config('supportflow.webhooks.signing_secret');

        return hash_hmac('sha256', $rawBody, $secret);
    }
}
