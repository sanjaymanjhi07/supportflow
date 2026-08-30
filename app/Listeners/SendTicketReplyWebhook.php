<?php

namespace App\Listeners;

use App\Events\TicketReplied;
use App\Http\Resources\TicketReplyResource;
use App\Services\SlaService;
use App\Services\WebhookDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketReplyWebhook implements ShouldQueue
{
    public function __construct(
        private readonly WebhookDispatcher $dispatcher,
        private readonly SlaService $sla,
    ) {
    }

    public function handle(TicketReplied $event): void
    {
        $reply = $event->reply;
        $ticket = $reply->ticket;

        // An agent (non-requester) reply counts as the ticket's first response.
        if ($reply->author_id !== $ticket->requester_id && ! $reply->is_internal_note) {
            $this->sla->markFirstResponse($ticket);
        }

        $this->dispatcher->dispatch(
            $ticket->tenant,
            'ticket.replied',
            (new TicketReplyResource($reply))->resolve()
        );
    }
}
