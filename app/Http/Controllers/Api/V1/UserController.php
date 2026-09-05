<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('tenant_id', $request->user()->tenant_id)
            ->with('roles')
            ->get();

        return response()->json(UserResource::collection($users));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'agent', 'customer'])],
        ]);

        $temporaryPassword = Str::random(16);

        $user = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
        ]);

        $user->assignRole($data['role']);

        // In production this would be emailed via a notification rather
        // than returned in the response body.
        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'temporary_password' => $temporaryPassword,
        ], 201);
    }
}
