<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent']);
            $table->unsignedInteger('first_response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
