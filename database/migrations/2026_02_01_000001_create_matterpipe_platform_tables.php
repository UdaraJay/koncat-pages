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
        Schema::create('workspaces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'slug']);
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('owner_type');
            $table->ulid('owner_id');
            $table->foreignUlid('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('current_deployment_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('deployments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->json('manifest')->nullable();
            $table->unsignedInteger('file_count')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->timestamp('deployed_at');
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('current_deployment_id')
                ->references('id')
                ->on('deployments')
                ->nullOnDelete();
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('collection');
            $table->json('data');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'collection']);
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('user_api_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_api_tokens');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project_documents');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['current_deployment_id']);
        });

        Schema::dropIfExists('deployments');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
