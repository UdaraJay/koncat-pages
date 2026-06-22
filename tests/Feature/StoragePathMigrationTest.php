<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StoragePathMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_path_migration_dry_run_does_not_change_storage_or_database(): void
    {
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        [$team, $deployment] = $this->deploymentFixture('old-deployments/app');

        Storage::disk('local')->put('old-deployments/app/index.html', 'hello');

        $this
            ->artisan('matterpipe:migrate-storage-paths', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('old-deployments/app', $deployment->fresh()->path);
        Storage::disk('local')->assertExists('old-deployments/app/index.html');
        Storage::disk('local')->assertMissing("teams/{$team->id}/projects/{$deployment->project_id}/deployments/{$deployment->id}/index.html");
    }

    public function test_storage_path_migration_moves_all_persisted_assets(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['matterpipe.storage_disk' => 'local']);

        [$team, $deployment, $file] = $this->assetFixture();

        Storage::disk('public')->put('team-branding/logo.png', 'logo');
        Storage::disk('local')->put('deployments/workspace/project/deployment/index.html', 'index');
        Storage::disk('local')->put("project-files/workspace/{$file->project_id}/{$file->id}-upload.png", 'upload');

        $this
            ->artisan('matterpipe:migrate-storage-paths')
            ->assertSuccessful();

        $team->refresh();
        $deployment->refresh();
        $file->refresh();

        $this->assertSame("teams/{$team->id}/branding/logo.png", $team->brand_logo_path);
        $this->assertSame("teams/{$team->id}/projects/{$deployment->project_id}/deployments/{$deployment->id}", $deployment->path);
        $this->assertSame("teams/{$team->id}/projects/{$file->project_id}/files/{$file->id}-upload.png", $file->path);

        Storage::disk('public')->assertMissing('team-branding/logo.png');
        Storage::disk('local')->assertMissing('deployments/workspace/project/deployment/index.html');
        Storage::disk('local')->assertMissing("project-files/workspace/{$file->project_id}/{$file->id}-upload.png");
        Storage::disk('public')->assertExists($team->brand_logo_path);
        Storage::disk('local')->assertExists($deployment->path.'/index.html');
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_storage_path_migration_skips_already_canonical_paths(): void
    {
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        [$team, $deployment] = $this->deploymentFixture('');
        $deployment->update([
            'path' => "teams/{$team->id}/projects/{$deployment->project_id}/deployments/{$deployment->id}",
        ]);
        Storage::disk('local')->put($deployment->path.'/index.html', 'hello');

        $this
            ->artisan('matterpipe:migrate-storage-paths')
            ->assertSuccessful();

        $this->assertSame("teams/{$team->id}/projects/{$deployment->project_id}/deployments/{$deployment->id}", $deployment->fresh()->path);
        Storage::disk('local')->assertExists($deployment->path.'/index.html');
    }

    public function test_storage_path_migration_fails_when_sources_are_missing(): void
    {
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        $this->deploymentFixture('missing/deployment');

        $this
            ->artisan('matterpipe:migrate-storage-paths')
            ->assertFailed();
    }

    public function test_storage_path_migration_rejects_target_inside_source_directory(): void
    {
        Storage::fake('local');
        config(['matterpipe.storage_disk' => 'local']);

        [$team, $deployment] = $this->deploymentFixture('placeholder');
        $deployment->update([
            'path' => "teams/{$team->id}/projects",
        ]);
        Storage::disk('local')->put("teams/{$team->id}/projects/old/index.html", 'old');

        $this
            ->artisan('matterpipe:migrate-storage-paths')
            ->assertFailed();

        $this->assertSame("teams/{$team->id}/projects", $deployment->fresh()->path);
        Storage::disk('local')->assertExists("teams/{$team->id}/projects/old/index.html");
    }

    public function test_storage_path_migration_reports_delete_failures_after_database_update(): void
    {
        config(['matterpipe.storage_disk' => 'local']);

        [$team, $deployment] = $this->deploymentFixture('old-deployments/delete-fails');

        $disk = Mockery::mock();
        $disk->shouldReceive('allFiles')
            ->with('old-deployments/delete-fails')
            ->andReturn(['old-deployments/delete-fails/index.html']);
        $disk->shouldReceive('get')
            ->with('old-deployments/delete-fails/index.html')
            ->andReturn('hello');
        $disk->shouldReceive('put')
            ->with("teams/{$team->id}/projects/{$deployment->project_id}/deployments/{$deployment->id}/index.html", 'hello')
            ->andReturn(true);
        $disk->shouldReceive('deleteDirectory')
            ->with('old-deployments/delete-fails')
            ->andReturn(false);
        Storage::shouldReceive('disk')
            ->with('local')
            ->andReturn($disk);

        $this
            ->artisan('matterpipe:migrate-storage-paths')
            ->expectsOutputToContain('manual cleanup')
            ->assertFailed();

        $this->assertSame("teams/{$team->id}/projects/{$deployment->project_id}/deployments/{$deployment->id}", $deployment->fresh()->path);
    }

    /**
     * @return array{0: Team, 1: Deployment, 2: ProjectFile}
     */
    protected function assetFixture(): array
    {
        [$team, $deployment, $project] = $this->deploymentFixture('deployments/workspace/project/deployment');

        $team->update(['brand_logo_path' => 'team-branding/logo.png']);

        $file = ProjectFile::create([
            'project_id' => $project->id,
            'uploaded_by' => null,
            'disk' => 'local',
            'path' => "project-files/workspace/{$project->id}/placeholder",
            'original_name' => 'upload.png',
            'mime_type' => 'image/png',
            'size' => 6,
        ]);
        $file->update(['path' => "project-files/workspace/{$project->id}/{$file->id}-upload.png"]);

        return [$team, $deployment, $file];
    }

    /**
     * @return array{0: Team, 1: Deployment, 2: Project}
     */
    protected function deploymentFixture(string $path): array
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $project = Project::factory()->create([
            'owner_type' => Team::class,
            'owner_id' => $team->id,
            'hosting_team_id' => $team->id,
            'created_by' => $user->id,
            'slug' => fake()->unique()->slug(),
        ]);
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => $path,
            'file_count' => 1,
            'total_bytes' => 5,
            'deployed_at' => now(),
        ]);

        return [$team, $deployment, $project];
    }
}
