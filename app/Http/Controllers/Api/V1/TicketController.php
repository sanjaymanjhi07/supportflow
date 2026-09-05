<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Ticket::query()
            ->where('tenant_id', $user->tenant_id)
            ->with(['category', 'requester', 'assignee'])
            ->withCount('replies')
            ->latest();

        // Customers only ever see their own tickets.
        if (! $user->hasAnyRole(['owner', 'admin', 'agent'])) {
            $query->where('requester_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($request->boolean('breached')) {
            $query->breached();
        }

        if ($assignedTo = $request->query('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        $tickets = $query->paginate((int) $request->query('per_page', 20));

        return response()->json(TicketResource::collection($tickets)->response()->getData(true));
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
            'requester_id' => $request->user()->id,
        ]);

        event(new TicketCreated($ticket));

        return response()->json(
            new TicketResource($ticket->fresh(['category', 'requester', 'assignee'])),
            201
        );
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load(['category', 'requester', 'assignee', 'replies.author']);

        return response()->json(new TicketResource($ticket));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket->update($request->validated());

        if ($request->has('status') && in_array($request->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]) && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();
            $ticket->save();
        }

        return response()->json(new TicketResource($ticket->fresh(['category', 'requester', 'assignee'])));
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }
}
