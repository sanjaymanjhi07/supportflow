<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\SlaPolicyController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketReplyController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // --- Public ---
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // --- Authenticated (Sanctum) ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::apiResource('tickets', TicketController::class);
        Route::get('/tickets/{ticket}/replies', [TicketReplyController::class, 'index']);
        Route::post('/tickets/{ticket}/replies', [TicketReplyController::class, 'store']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/sla-policies', [SlaPolicyController::class, 'index']);
        Route::post('/sla-policies', [SlaPolicyController::class, 'store']);
        Route::delete('/sla-policies/{slaPolicy}', [SlaPolicyController::class, 'destroy']);

        Route::get('/webhooks', [WebhookController::class, 'index']);
        Route::post('/webhooks', [WebhookController::class, 'store']);
        Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy']);

        // Owner/admin-only user management, enforced inside the controller
        // and additionally guarded here via the role middleware.
        Route::middleware('role:owner,admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
        });
    });
});
