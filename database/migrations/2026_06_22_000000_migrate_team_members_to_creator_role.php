<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('team_members')
            ->where('role', 'member')
            ->update(['role' => 'creator']);

        DB::table('team_invitations')
            ->where('role', 'member')
            ->update(['role' => 'creator']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('team_members')
            ->where('role', 'creator')
            ->update(['role' => 'member']);

        DB::table('team_invitations')
            ->where('role', 'creator')
            ->update(['role' => 'member']);
    }
};
