<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookDispatcher;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public array $backoff = [10, 60, 300, 900];

    public function __construct(
        public Webhook $webhook,
        public string $event,
        public array $payload,
    ) {
        $this->tries = config('supportflow.webhooks.max_attempts', 5);
        $this->onQueue('webhooks');
    }

    public function handle(WebhookDispatcher $dispatcher, Client $client): void
    {
        $body = json_encode($this->payload, JSON_UNESCAPED_SLASHES);
        $signature = $dispatcher->sign($this->webhook, $body);

        $delivery = new WebhookDelivery([
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'payload' => $this->payload,
            'attempt' => $this->attempts(),
        ]);

        try {
            $response = $client->post($this->webhook->url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-SupportFlow-Event' => $this->event,
                    'X-SupportFlow-Signature' => $signature,
                ],
                'body' => $body,
                'timeout' => config('supportflow.webhooks.timeout_seconds', 5),
                'http_errors' => true,
            ]);

            $delivery->response_status = $response->getStatusCode();
            $delivery->response_body = substr((string) $response->getBody(), 0, 2000);
            $delivery->succeeded = $response->getStatusCode() < 300;
            $delivery->save();
        } catch (RequestException $exception) {
            $delivery->response_status = $exception->getResponse()?->getStatusCode();
            $delivery->response_body = substr($exception->getMessage(), 0, 2000);
            $delivery->succeeded = false;
            $delivery->save();

            // Let Laravel's queue retry mechanism apply the backoff schedule.
            throw $exception;
        }
    }
}
