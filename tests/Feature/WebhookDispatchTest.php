<?php

namespace Tests\Feature;

use App\Jobs\DispatchWebhookJob;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WebhookDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_ticket_queues_a_webhook_delivery_for_subscribed_endpoints(): void
    {
        Bus::fake();

        $tenant = Tenant::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        Role::findOrCreate('customer', 'api');

        $customer = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer->assignRole('customer');

        Webhook::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com/hooks/supportflow',
            'events' => ['ticket.created'],
            'is_active' => true,
        ]);

        // A webhook not subscribed to ticket.created should never fire.
        Webhook::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com/hooks/other',
            'events' => ['ticket.replied'],
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'sanctum')->postJson('/api/v1/tickets', [
            'subject' => 'Webhook test ticket',
            'description' => 'Checking that webhooks fire on creation.',
        ])->assertCreated();

        Bus::assertDispatched(DispatchWebhookJob::class, function (DispatchWebhookJob $job) {
            return $job->event === 'ticket.created'
                && $job->webhook->url === 'https://example.com/hooks/supportflow';
        });

        Bus::assertNotDispatched(DispatchWebhookJob::class, function (DispatchWebhookJob $job) {
            return $job->webhook->url === 'https://example.com/hooks/other';
        });
    }
}
