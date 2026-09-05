<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('tenant_id', $request->user()->tenant_id)
            ->withCount('deliveries')
            ->get();

        return response()->json($webhooks);
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = Webhook::create([
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
        ]);

        // The plaintext secret is only ever returned once, at creation time,
        // so the caller can store it. The model hides it on all future reads.
        return response()->json([
            ...$webhook->toArray(),
            'secret' => $webhook->getRawOriginal('secret'),
        ], 201);
    }

    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        if ($webhook->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (! $request->user()->hasAnyRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $webhook->delete();

        return response()->json(null, 204);
    }
}
