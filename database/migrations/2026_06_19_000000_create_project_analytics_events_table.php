<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_analytics_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->text('path')->nullable();
            $table->timestamp('occurred_at');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'event_type', 'occurred_at']);
            $table->index(['project_id', 'user_id']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_analytics_events');
    }
};
