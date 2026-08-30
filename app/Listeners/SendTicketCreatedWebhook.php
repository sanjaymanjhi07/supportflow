<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Http\Resources\TicketResource;
use App\Services\WebhookDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketCreatedWebhook implements ShouldQueue
{
    public function __construct(private readonly WebhookDispatcher $dispatcher)
    {
    }

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        $this->dispatcher->dispatch(
            $ticket->tenant,
            'ticket.created',
            (new TicketResource($ticket))->resolve()
        );
    }
}
