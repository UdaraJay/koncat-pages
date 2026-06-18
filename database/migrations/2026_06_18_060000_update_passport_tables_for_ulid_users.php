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
        if (Schema::hasTable('oauth_auth_codes')) {
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                $table->ulid('user_id')->change();
            });
        }

        if (Schema::hasTable('oauth_access_tokens')) {
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                $table->ulid('user_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('oauth_device_codes')) {
            Schema::table('oauth_device_codes', function (Blueprint $table) {
                $table->ulid('user_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('oauth_clients') && Schema::hasColumn('oauth_clients', 'owner_id')) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                $table->ulid('owner_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('oauth_auth_codes')) {
            Schema::table('oauth_auth_codes', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->change();
            });
        }

        if (Schema::hasTable('oauth_access_tokens')) {
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('oauth_device_codes')) {
            Schema::table('oauth_device_codes', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('oauth_clients') && Schema::hasColumn('oauth_clients', 'owner_id')) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_id')->nullable()->change();
            });
        }
    }

    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
