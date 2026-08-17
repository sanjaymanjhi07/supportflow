<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    |
    | The fixed set of roles SupportFlow ships with. Tenants can be extended
    | with custom roles via spatie/laravel-permission, but these are the
    | roles the application logic (policies, seeders) understands natively.
    |
    */
    'roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'agent' => 'Agent',
        'customer' => 'Customer',
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA defaults
    |--------------------------------------------------------------------------
    |
    | Used whenever a tenant has not configured a custom SLA policy for a
    | given ticket priority.
    |
    */
    'sla' => [
        'default_first_response_minutes' => env('SLA_DEFAULT_FIRST_RESPONSE_MINUTES', 60),
        'default_resolution_minutes' => env('SLA_DEFAULT_RESOLUTION_MINUTES', 1440),

        // Priorities recognised by the system, mapped to a default multiplier
        // applied to the base minutes above (lower = tighter SLA).
        'priority_multipliers' => [
            'urgent' => 0.25,
            'high' => 0.5,
            'normal' => 1,
            'low' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'signing_secret' => env('WEBHOOK_SIGNING_SECRET', 'change-me'),
        'max_attempts' => (int) env('WEBHOOK_MAX_ATTEMPTS', 5),
        'timeout_seconds' => (int) env('WEBHOOK_TIMEOUT_SECONDS', 5),
        'events' => [
            'ticket.created',
            'ticket.updated',
            'ticket.replied',
            'ticket.status_changed',
            'ticket.sla_breached',
        ],
    ],
];
