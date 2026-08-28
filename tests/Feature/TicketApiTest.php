<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUserWithRole(Tenant $tenant, string $role): User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        Role::findOrCreate($role, 'api');

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_customer_can_create_a_ticket(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = $this->makeUserWithRole($tenant, 'customer');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/tickets', [
                'subject' => 'My printer will not connect',
                'description' => 'It was working yesterday and now it is offline.',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.subject', 'My printer will not connect')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.sla.breached', false);

        $this->assertDatabaseHas('tickets', [
            'tenant_id' => $tenant->id,
            'requester_id' => $customer->id,
            'subject' => 'My printer will not connect',
        ]);
    }

    public function test_ticket_receives_sla_due_dates_on_creation(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = $this->makeUserWithRole($tenant, 'customer');

        $this->actingAs($customer, 'sanctum')->postJson('/api/v1/tickets', [
            'subject' => 'Urgent billing issue',
            'description' => 'I was double charged this month.',
            'priority' => 'urgent',
        ])->assertCreated();

        $ticket = Ticket::first();

        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);
        $this->assertTrue($ticket->first_response_due_at->isFuture());
    }

    public function test_a_user_cannot_view_a_ticket_belonging_to_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $agentA = $this->makeUserWithRole($tenantA, 'agent');
        $customerB = $this->makeUserWithRole($tenantB, 'customer');

        $ticket = Ticket::factory()->forTenant($customerB)->create();

        $this->actingAs($agentA, 'sanctum')
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_customer_cannot_see_another_customers_ticket(): void
    {
        $tenant = Tenant::factory()->create();
        $customerA = $this->makeUserWithRole($tenant, 'customer');
        $customerB = $this->makeUserWithRole($tenant, 'customer');

        $ticket = Ticket::factory()->forTenant($customerB)->create();

        $this->actingAs($customerA, 'sanctum')
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_agent_can_list_all_tenant_tickets(): void
    {
        $tenant = Tenant::factory()->create();
        $agent = $this->makeUserWithRole($tenant, 'agent');
        $customer = $this->makeUserWithRole($tenant, 'customer');

        Ticket::factory()->forTenant($customer)->count(3)->create();

        $this->actingAs($agent, 'sanctum')
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
