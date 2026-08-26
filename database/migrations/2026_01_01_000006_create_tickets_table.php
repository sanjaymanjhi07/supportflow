<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference')->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('sla_breached')->default(false);

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['resolution_due_at']);
            $table->index(['first_response_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
