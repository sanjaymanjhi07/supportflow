<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('tenant_id', $request->user()->tenant_id)->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $category = Category::create([
            ...$data,
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return response()->json($category, 201);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        if ($category->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (! $request->user()->hasAnyRole(['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
