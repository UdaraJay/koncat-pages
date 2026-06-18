<?php

use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('subdomain', 63)->nullable()->after('slug')->unique();
        });

        DB::table('teams')
            ->orderBy('id')
            ->get(['id', 'name', 'slug'])
            ->each(function (object $team): void {
                DB::table('teams')
                    ->where('id', $team->id)
                    ->update(['subdomain' => $this->uniqueSubdomain((string) ($team->slug ?: $team->name), (string) $team->id)]);
            });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignUlid('hosting_team_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained('teams')
                ->nullOnDelete();
        });

        DB::table('projects')
            ->orderBy('id')
            ->get(['id', 'owner_type', 'owner_id', 'workspace_id'])
            ->each(function (object $project): void {
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['hosting_team_id' => $this->hostingTeamId($project)]);
            });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique('projects_slug_unique');
            $table->unique(['hosting_team_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['hosting_team_id', 'slug']);
            $table->unique('slug');
            $table->dropConstrainedForeignId('hosting_team_id');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropColumn('subdomain');
        });
    }

    protected function hostingTeamId(object $project): ?string
    {
        if ($project->workspace_id) {
            return DB::table('workspaces')
                ->where('id', $project->workspace_id)
                ->value('team_id');
        }

        if ($project->owner_type === Team::class) {
            return $project->owner_id;
        }

        return DB::table('teams')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $project->owner_id)
            ->where('teams.is_personal', true)
            ->value('teams.id');
    }

    protected function uniqueSubdomain(string $value, string $ignoreId): string
    {
        $base = Str::slug($value) ?: 'team';
        $base = trim(Str::limit($base, 63, ''), '-');
        $subdomain = $base;
        $suffix = 1;

        while (DB::table('teams')
            ->where('id', '!=', $ignoreId)
            ->where('subdomain', $subdomain)
            ->exists()) {
            $suffixText = '-'.$suffix;
            $subdomain = trim(Str::limit($base, 63 - strlen($suffixText), ''), '-').$suffixText;
            $suffix++;
        }

        return $subdomain;
    }
};
