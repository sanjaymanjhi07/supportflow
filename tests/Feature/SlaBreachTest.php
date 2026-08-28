<?php

namespace Tests\Feature;

use App\Events\TicketSlaBreached;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SlaBreachTest extends TestCase
{
    use RefreshDatabase;

    public function test_detect_breaches_flags_tickets_past_their_due_dates(): void
    {
        Event::fake([TicketSlaBreached::class]);

        $tenant = Tenant::factory()->create();
        $requester = User::factory()->create(['tenant_id' => $tenant->id]);

        $overdue = Ticket::factory()->create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'status' => 'open',
            'first_response_due_at' => now()->subMinutes(5),
            'resolution_due_at' => now()->addDay(),
            'sla_breached' => false,
        ]);

        $onTrack = Ticket::factory()->create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'status' => 'open',
            'first_response_due_at' => now()->addHour(),
            'resolution_due_at' => now()->addDay(),
            'sla_breached' => false,
        ]);

        $breachedCount = app(SlaService::class)->detectBreaches();

        $this->assertEquals(1, $breachedCount);
        $this->assertTrue($overdue->fresh()->sla_breached);
        $this->assertFalse($onTrack->fresh()->sla_breached);

        Event::assertDispatched(TicketSlaBreached::class, function ($event) use ($overdue) {
            return $event->ticket->id === $overdue->id;
        });
    }

    public function test_resolved_tickets_are_not_flagged_even_if_overdue(): void
    {
        $tenant = Tenant::factory()->create();
        $requester = User::factory()->create(['tenant_id' => $tenant->id]);

        Ticket::factory()->create([
            'tenant_id' => $tenant->id,
            'requester_id' => $requester->id,
            'status' => 'resolved',
            'resolved_at' => now(),
            'first_response_due_at' => now()->subDay(),
            'resolution_due_at' => now()->subHour(),
            'sla_breached' => false,
        ]);

        $breachedCount = app(SlaService::class)->detectBreaches();

        $this->assertEquals(0, $breachedCount);
    }
}
