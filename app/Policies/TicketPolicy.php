<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * No user may ever act on a ticket belonging to another tenant,
     * regardless of role.
     */
    protected function sameTenant(User $user, Ticket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if (! $this->sameTenant($user, $ticket)) {
            return false;
        }

        if ($user->hasAnyRole(['owner', 'admin', 'agent'])) {
            return true;
        }

        // Customers may only view their own tickets.
        return $ticket->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (! $this->sameTenant($user, $ticket)) {
            return false;
        }

        return $user->hasAnyRole(['owner', 'admin', 'agent']);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->sameTenant($user, $ticket) && $user->hasAnyRole(['owner', 'admin']);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if (! $this->sameTenant($user, $ticket)) {
            return false;
        }

        if ($user->hasAnyRole(['owner', 'admin', 'agent'])) {
            return true;
        }

        return $ticket->requester_id === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->sameTenant($user, $ticket) && $user->hasRole('owner');
    }
}
