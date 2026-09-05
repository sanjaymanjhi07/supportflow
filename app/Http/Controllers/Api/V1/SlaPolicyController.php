<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SlaPolicyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $policies = SlaPolicy::where('tenant_id', $request->user()->tenant_id)->get();

        return response()->json($policies);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'first_response_minutes' => ['required', 'integer', 'min:1'],
            'resolution_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $policy = SlaPolicy::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id, 'priority' => $data['priority']],
            $data
        );

        return response()->json($policy, 201);
    }

    public function destroy(Request $request, SlaPolicy $slaPolicy): JsonResponse
    {
        if ($slaPolicy->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (! $request->user()->hasAnyRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $slaPolicy->delete();

        return response()->json(null, 204);
    }
}
