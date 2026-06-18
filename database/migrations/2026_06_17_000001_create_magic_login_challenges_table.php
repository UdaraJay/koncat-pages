<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magic_login_challenges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('email')->index();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose')->index();
            $table->string('token_hash', 64)->index();
            $table->string('code_hash', 64);
            $table->boolean('remember')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });

        DB::table('users')->update(['password' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereNull('password')
            ->update(['password' => Hash::make(Str::random(32))]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });

        Schema::dropIfExists('magic_login_challenges');
    }
};
