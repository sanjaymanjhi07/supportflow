<?php

namespace App\Services;

use App\Events\TicketSlaBreached;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\Carbon;

class SlaService
{
    /**
     * Resolve the SLA policy that applies to a ticket, falling back to
     * config-driven defaults if the tenant hasn't defined one for this
     * priority.
     */
    public function resolvePolicy(Ticket $ticket): array
    {
        $policy = SlaPolicy::query()
            ->where('tenant_id', $ticket->tenant_id)
            ->where('priority', $ticket->priority)
            ->first();

        if ($policy) {
            return [
                'first_response_minutes' => $policy->first_response_minutes,
                'resolution_minutes' => $policy->resolution_minutes,
            ];
        }

        $multiplier = config("supportflow.sla.priority_multipliers.{$ticket->priority}", 1);

        return [
            'first_response_minutes' => (int) round(
                config('supportflow.sla.default_first_response_minutes') * $multiplier
            ),
            'resolution_minutes' => (int) round(
                config('supportflow.sla.default_resolution_minutes') * $multiplier
            ),
        ];
    }

    /**
     * Stamp a freshly created (or re-prioritised) ticket with its SLA
     * due dates, measured from now.
     */
    public function applyToTicket(Ticket $ticket): Ticket
    {
        $policy = $this->resolvePolicy($ticket);
        $now = Carbon::now();

        $ticket->first_response_due_at = $now->copy()->addMinutes($policy['first_response_minutes']);
        $ticket->resolution_due_at = $now->copy()->addMinutes($policy['resolution_minutes']);
        $ticket->save();

        return $ticket;
    }

    /**
     * Record that the first agent response has happened, if it hasn't
     * already been recorded for this ticket.
     */
    public function markFirstResponse(Ticket $ticket): void
    {
        if ($ticket->first_responded_at) {
            return;
        }

        $ticket->first_responded_at = Carbon::now();
        $ticket->save();
    }

    /**
     * Sweep every tenant's open tickets for SLA breaches. Intended to be
     * run frequently from the scheduler (see App\Console\Commands\CheckSlaBreaches).
     *
     * Returns the number of tickets newly marked as breached.
     */
    public function detectBreaches(): int
    {
        $breached = 0;

        Ticket::query()
            ->open()
            ->where('sla_breached', false)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('first_responded_at')
                        ->whereNotNull('first_response_due_at')
                        ->where('first_response_due_at', '<', now());
                })->orWhere(function ($q) {
                    $q->whereNull('resolved_at')
                        ->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<', now());
                });
            })
            ->chunkById(200, function ($tickets) use (&$breached) {
                foreach ($tickets as $ticket) {
                    $ticket->sla_breached = true;
                    $ticket->save();
                    event(new TicketSlaBreached($ticket));
                    $breached++;
                }
            });

        return $breached;
    }
}
