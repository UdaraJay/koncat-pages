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
        Schema::create('project_shares', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('permission');
            $table->foreignUlid('shared_by')->constrained('users')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->timestamps();

            $table->unique(['project_id', 'email']);
            $table->unique(['project_id', 'user_id']);
            $table->index(['email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_shares');
    }
};
