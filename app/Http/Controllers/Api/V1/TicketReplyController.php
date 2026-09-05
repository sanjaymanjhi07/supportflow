<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TicketReplied;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketReplyController extends Controller
{
    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $replies = $ticket->replies()->with('author')->get();

        // Customers never see internal notes.
        if (! $request->user()->hasAnyRole(['owner', 'admin', 'agent'])) {
            $replies = $replies->where('is_internal_note', false)->values();
        }

        return response()->json(TicketReplyResource::collection($replies));
    }

    public function store(StoreTicketReplyRequest $request, Ticket $ticket): JsonResponse
    {
        $reply = $ticket->replies()->create([
            'author_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_internal_note' => $request->boolean('is_internal_note'),
        ]);

        // Replying reopens a resolved ticket automatically, unless it's an
        // internal note only visible to staff.
        if (! $reply->is_internal_note && in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
            $ticket->update(['status' => Ticket::STATUS_OPEN, 'resolved_at' => null]);
        }

        event(new TicketReplied($reply));

        return response()->json(new TicketReplyResource($reply->load('author')), 201);
    }
}
