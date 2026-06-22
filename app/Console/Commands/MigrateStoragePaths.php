<?php

namespace App\Console\Commands;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Team;
use App\Services\TeamStoragePaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateStoragePaths extends Command
{
    protected $signature = 'matterpipe:migrate-storage-paths
        {--dry-run : Report planned moves without changing storage or DB}
        {--team= : Only migrate one team ULID}';

    protected $description = 'Move persisted storage paths into the canonical team-scoped layout.';

    /**
     * @var array<string, int>
     */
    protected array $totals = [
        'copied' => 0,
        'updated' => 0,
        'deleted' => 0,
        'current' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    /**
     * @var array<int, string>
     */
    protected array $cleanupFailures = [];

    public function handle(TeamStoragePaths $paths): int
    {
        $teamId = $this->option('team') ? (string) $this->option('team') : null;
        $dryRun = (bool) $this->option('dry-run');

        $this->line($dryRun ? 'Running storage path migration dry run.' : 'Running storage path migration.');

        $this->migrateBranding($paths, $teamId, $dryRun);
        $this->migrateDeployments($paths, $teamId, $dryRun);
        $this->migrateProjectFiles($paths, $teamId, $dryRun);

        $this->newLine();
        $this->table(
            ['Copied', 'Updated', 'Deleted', 'Current', 'Skipped', 'Failed'],
            [[
                $this->totals['copied'],
                $this->totals['updated'],
                $this->totals['deleted'],
                $this->totals['current'],
                $this->totals['skipped'],
                $this->totals['failed'],
            ]],
        );

        if ($this->cleanupFailures !== []) {
            $this->newLine();
            $this->warn('These old paths still need manual cleanup:');

            foreach ($this->cleanupFailures as $path) {
                $this->line(" - {$path}");
            }
        }

        return $this->totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function migrateBranding(TeamStoragePaths $paths, ?string $teamId, bool $dryRun): void
    {
        Team::withTrashed()
            ->whereNotNull('brand_logo_path')
            ->when($teamId, fn ($query) => $query->whereKey($teamId))
            ->orderBy('id')
            ->each(function (Team $team) use ($paths, $dryRun): void {
                $source = (string) $team->brand_logo_path;
                $target = $paths->brandingLogoPath($team->id, basename($source));

                $this->migrateObject(
                    disk: 'public',
                    source: $source,
                    target: $target,
                    label: "team {$team->id} logo",
                    dryRun: $dryRun,
                    update: fn () => Team::withTrashed()->whereKey($team->id)->update(['brand_logo_path' => $target]),
                );
            });
    }

    protected function migrateDeployments(TeamStoragePaths $paths, ?string $teamId, bool $dryRun): void
    {
        Deployment::query()
            ->orderBy('id')
            ->each(function (Deployment $deployment) use ($paths, $teamId, $dryRun): void {
                $project = Project::withTrashed()->find($deployment->project_id);

                if (! $this->projectIsInScope($project, $teamId, "deployment {$deployment->id}")) {
                    return;
                }

                $target = $paths->deploymentDirectory($project, $deployment->id);

                $this->migrateDirectory(
                    disk: $deployment->disk,
                    source: $deployment->path,
                    target: $target,
                    label: "deployment {$deployment->id}",
                    dryRun: $dryRun,
                    update: fn () => Deployment::query()->whereKey($deployment->id)->update(['path' => $target]),
                );
            });
    }

    protected function migrateProjectFiles(TeamStoragePaths $paths, ?string $teamId, bool $dryRun): void
    {
        ProjectFile::query()
            ->orderBy('id')
            ->each(function (ProjectFile $file) use ($paths, $teamId, $dryRun): void {
                $project = Project::withTrashed()->find($file->project_id);

                if (! $this->projectIsInScope($project, $teamId, "project file {$file->id}")) {
                    return;
                }

                $basename = basename($file->path);
                $filename = str_starts_with($basename, "{$file->id}-")
                    ? substr($basename, strlen($file->id) + 1)
                    : $basename;
                $target = $paths->projectFilePath($project, $file, $filename);

                $this->migrateObject(
                    disk: $file->disk,
                    source: $file->path,
                    target: $target,
                    label: "project file {$file->id}",
                    dryRun: $dryRun,
                    update: fn () => ProjectFile::query()->whereKey($file->id)->update(['path' => $target]),
                );
            });
    }

    protected function projectIsInScope(?Project $project, ?string $teamId, string $label): bool
    {
        if (! $project instanceof Project) {
            if ($teamId === null) {
                $this->failRow($label, 'project not found');
            } else {
                $this->totals['skipped']++;
            }

            return false;
        }

        if ($teamId !== null && $project->hosting_team_id !== $teamId) {
            $this->totals['skipped']++;

            return false;
        }

        if (! $project->hosting_team_id) {
            $this->failRow($label, 'hosting team is missing');

            return false;
        }

        if (! Team::withTrashed()->whereKey($project->hosting_team_id)->exists()) {
            $this->failRow($label, "hosting team {$project->hosting_team_id} not found");

            return false;
        }

        return true;
    }

    protected function migrateObject(string $disk, string $source, string $target, string $label, bool $dryRun, callable $update): void
    {
        $storage = Storage::disk($disk);

        if ($source === $target) {
            if ($storage->exists($target)) {
                $this->totals['current']++;
                $this->line("Current {$label}: {$target}");
            } else {
                $this->failRow($label, "canonical object is missing at {$target}");
            }

            return;
        }

        if (! $storage->exists($source)) {
            $this->failRow($label, "source object is missing at {$source}");

            return;
        }

        $this->line(($dryRun ? 'Would move' : 'Moving')." {$label}: {$source} -> {$target}");

        if ($dryRun) {
            return;
        }

        try {
            $storage->put($target, $storage->get($source));
            $this->totals['copied']++;

            DB::transaction(fn () => $update());
            $this->totals['updated']++;

            if (! $storage->delete($source)) {
                $this->cleanupFailures[] = "{$disk}:{$source}";
                $this->failRow($label, "database updated, but unable to delete old object {$source}");

                return;
            }

            $this->totals['deleted']++;
        } catch (Throwable $exception) {
            $this->failRow($label, $exception->getMessage());
        }
    }

    protected function migrateDirectory(string $disk, string $source, string $target, string $label, bool $dryRun, callable $update): void
    {
        $storage = Storage::disk($disk);
        $sourceFiles = $storage->allFiles($source);
        $sourcePrefix = rtrim($source, '/').'/';

        if ($source === $target) {
            if ($sourceFiles !== []) {
                $this->totals['current']++;
                $this->line("Current {$label}: {$target}");
            } else {
                $this->failRow($label, "canonical directory is missing or empty at {$target}");
            }

            return;
        }

        if (str_starts_with(rtrim($target, '/').'/', $sourcePrefix)) {
            $this->failRow($label, "target directory {$target} cannot be inside source directory {$source}");

            return;
        }

        if ($sourceFiles === []) {
            $this->failRow($label, "source directory is missing or empty at {$source}");

            return;
        }

        $this->line(($dryRun ? 'Would move' : 'Moving')." {$label}: {$source} -> {$target}");

        if ($dryRun) {
            return;
        }

        try {
            foreach ($sourceFiles as $sourceFile) {
                $relativePath = str($sourceFile)->after(rtrim($source, '/').'/')->toString();
                $storage->put("{$target}/{$relativePath}", $storage->get($sourceFile));
                $this->totals['copied']++;
            }

            DB::transaction(fn () => $update());
            $this->totals['updated']++;

            if (! $storage->deleteDirectory($source)) {
                $this->cleanupFailures[] = "{$disk}:{$source}";
                $this->failRow($label, "database updated, but unable to delete old directory {$source}");

                return;
            }

            $this->totals['deleted']++;
        } catch (Throwable $exception) {
            $this->failRow($label, $exception->getMessage());
        }
    }

    protected function failRow(string $label, string $message): void
    {
        $this->totals['failed']++;
        $this->error("Failed {$label}: {$message}");
    }
}
