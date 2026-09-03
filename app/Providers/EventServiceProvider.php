<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Events\TicketReplied;
use App\Events\TicketSlaBreached;
use App\Listeners\ApplySlaPolicy;
use App\Listeners\SendTicketCreatedWebhook;
use App\Listeners\SendTicketReplyWebhook;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TicketCreated::class => [
            ApplySlaPolicy::class,
            SendTicketCreatedWebhook::class,
        ],
        TicketReplied::class => [
            SendTicketReplyWebhook::class,
        ],
        TicketSlaBreached::class => [
            // Additional listeners (e.g. escalation notifications) can be
            // registered here without touching the ticket lifecycle code.
        ],
    ];

    public function boot(): void
    {
        //
    }
}
