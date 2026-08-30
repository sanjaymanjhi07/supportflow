<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Services\SlaService;

class ApplySlaPolicy
{
    public function __construct(private readonly SlaService $sla)
    {
    }

    /**
     * Runs synchronously (not queued) so the ticket already carries its
     * due dates by the time the API response is returned.
     */
    public function handle(TicketCreated $event): void
    {
        $this->sla->applyToTicket($event->ticket);
    }
}
