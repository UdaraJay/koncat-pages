<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Validation\ValidationException;

class MatterpipeQuota
{
    public function __construct(protected MatterpipeLimitResolver $limits)
    {
        //
    }

    public function ensureTeamCanCreateWorkspace(Team $team): void
    {
        $this->ensureLimit(
            $team->workspaces()->count(),
            $this->limits->teamWorkspaces($team),
            'This team has reached its workspace limit.',
        );
    }

    public function ensureUserCanCreateProject(User $user): void
    {
        $this->ensureLimit(
            $user->projects()->withTrashed()->count(),
            $this->limits->userProjects($user),
            'Your account has reached its project limit.',
        );
    }

    public function ensureTeamCanCreateProject(Team $team): void
    {
        $this->ensureLimit(
            $team->projects()->withTrashed()->count(),
            $this->limits->teamProjects($team),
            'This team has reached its project limit.',
        );
    }

    public function ensureWorkspaceCanCreateProject(Workspace $workspace): void
    {
        $this->ensureLimit(
            $workspace->projects()->withTrashed()->count(),
            $this->limits->workspaceProjects($workspace),
            'This workspace has reached its project limit.',
        );
    }

    public function ensureDeploymentWithinLimits(Project $project, int $bytes, int $files): void
    {
        $this->ensureLimit($files, $this->limits->deploymentFiles($project), 'This deployment has too many files.', inclusive: true);
        $this->ensureLimit($bytes, $this->limits->deploymentBytes($project), 'This deployment is too large.', inclusive: true);
    }

    public function ensureDeploymentFileWithinLimit(Project|User $subject, int $bytes): void
    {
        $this->ensureLimit(
            $bytes,
            $this->limits->deploymentFileBytes($subject),
            'This deployment contains a file that is too large.',
            inclusive: true,
        );
    }

    /**
     * @param  array<int, array{path: string, contents: string}>  $files
     */
    public function ensureDeploymentFilesWithinLimits(Project|User $subject, array $files): void
    {
        $this->ensureLimit(count($files), $this->limits->deploymentFiles($subject), 'This deployment has too many files.', inclusive: true);

        $totalBytes = 0;

        foreach ($files as $file) {
            $bytes = strlen($file['contents']);
            $this->ensureDeploymentFileWithinLimit($subject, $bytes);
            $totalBytes += $bytes;
        }

        $this->ensureLimit($totalBytes, $this->limits->deploymentBytes($subject), 'This deployment is too large.', inclusive: true);
    }

    public function ensureProjectCanCreateDocument(Project $project): void
    {
        $this->ensureLimit(
            $project->documents()->count(),
            $this->limits->projectDocuments($project),
            'This project has reached its document limit.',
        );
    }

    public function ensureProjectCanStoreFile(Project $project, int $additionalBytes): void
    {
        $this->ensureLimit(
            $additionalBytes,
            $this->limits->projectFileUploadBytes($project),
            'The uploaded file is too large.',
            inclusive: true,
        );

        $this->ensureLimit(
            $project->files()->count(),
            $this->limits->projectFiles($project),
            'This project has reached its file limit.',
        );

        $storedBytes = (int) $project->files()->sum('size');
        $this->ensureLimit(
            $storedBytes + $additionalBytes,
            $this->limits->projectFileBytes($project),
            'This project has reached its file storage limit.',
            inclusive: true,
        );
    }

    protected function ensureLimit(int $current, int $limit, string $message, bool $inclusive = false): void
    {
        $exceeded = $inclusive ? $current > $limit : $current >= $limit;

        if ($limit > 0 && $exceeded) {
            throw ValidationException::withMessages(['quota' => $message]);
        }
    }
}
