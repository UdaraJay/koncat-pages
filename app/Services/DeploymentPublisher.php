<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class DeploymentPublisher
{
    public function __construct(protected MatterpipeQuota $quota)
    {
        //
    }

    /**
     * @param  array<int, array{path: string, contents: string}>  $files
     */
    public function publishFiles(Project $project, array $files, ?User $user = null): Deployment
    {
        $path = tempnam(sys_get_temp_dir(), 'deployment-');

        if ($path === false) {
            throw ValidationException::withMessages(['files' => 'A temporary deployment archive could not be created.']);
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            throw ValidationException::withMessages(['files' => 'A temporary deployment archive could not be opened.']);
        }

        foreach ($files as $file) {
            $zip->addFromString($file['path'], $file['contents']);
        }

        $zip->close();

        try {
            return $this->publish(
                $project,
                new UploadedFile($path, 'deployment.zip', 'application/zip', null, true),
                $user,
            );
        } finally {
            @unlink($path);
        }
    }

    public function publish(Project $project, UploadedFile $archive, ?User $user = null): Deployment
    {
        $zip = new ZipArchive;

        if ($zip->open($archive->getRealPath()) !== true) {
            throw ValidationException::withMessages(['archive' => 'The deployment archive could not be opened.']);
        }

        [$manifest, $totalBytes, $fileCount, $hasIndex] = $this->inspect($zip);

        if (! $hasIndex) {
            $zip->close();

            throw ValidationException::withMessages(['archive' => 'The deployment archive must include an index.html file.']);
        }

        $this->quota->ensureDeploymentWithinLimits($totalBytes, $fileCount);

        $disk = (string) config('matterpipe.storage_disk');

        $projectScope = $project->workspace_id ?? $project->owner_id;

        return DB::transaction(function () use ($project, $archive, $user, $zip, $manifest, $totalBytes, $fileCount, $disk, $projectScope) {
            $deployment = Deployment::create([
                'project_id' => $project->id,
                'user_id' => $user?->id,
                'disk' => $disk,
                'path' => "deployments/{$projectScope}/{$project->id}/pending",
                'original_filename' => $archive->getClientOriginalName(),
                'manifest' => $manifest,
                'file_count' => $fileCount,
                'total_bytes' => $totalBytes,
                'deployed_at' => now(),
            ]);

            $deploymentPath = "deployments/{$projectScope}/{$project->id}/{$deployment->id}";
            $storage = Storage::disk($disk);

            foreach ($manifest as $file) {
                $contents = $zip->getFromName($file['path']);

                if ($contents === false) {
                    throw ValidationException::withMessages(['archive' => "Unable to read {$file['path']} from the archive."]);
                }

                $storage->put("{$deploymentPath}/{$file['path']}", $contents);
            }

            $zip->close();

            $deployment->update(['path' => $deploymentPath]);
            $project->update(['current_deployment_id' => $deployment->id]);

            return $deployment;
        });
    }

    /**
     * @return array{0: array<int, array{path: string, size: int}>, 1: int, 2: int, 3: bool}
     */
    protected function inspect(ZipArchive $zip): array
    {
        $manifest = [];
        $totalBytes = 0;
        $hasIndex = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if ($stat === false) {
                continue;
            }

            $path = str_replace('\\', '/', $stat['name']);

            if (str_ends_with($path, '/')) {
                continue;
            }

            $normalized = trim($path, '/');

            if ($this->isUnsafePath($path, $normalized)) {
                throw ValidationException::withMessages(['archive' => 'The deployment archive contains an unsafe file path.']);
            }

            if ($normalized === '__matterpipe' || str_starts_with($normalized, '__matterpipe/')) {
                throw ValidationException::withMessages(['archive' => 'The __matterpipe path is reserved by the platform.']);
            }

            $size = (int) $stat['size'];
            $totalBytes += $size;
            $hasIndex = $hasIndex || strtolower($normalized) === 'index.html';

            $manifest[] = [
                'path' => $normalized,
                'size' => $size,
            ];
        }

        if ($manifest === []) {
            throw ValidationException::withMessages(['archive' => 'The deployment archive is empty.']);
        }

        return [$manifest, $totalBytes, count($manifest), $hasIndex];
    }

    protected function isUnsafePath(string $path, string $normalized): bool
    {
        return $normalized === ''
            || str_starts_with($path, '/')
            || preg_match('/^[a-zA-Z]:[\/\\\\]/', $path) === 1
            || Str::contains($normalized, ['../', '/..'])
            || str_starts_with($normalized, '.');
    }
}
