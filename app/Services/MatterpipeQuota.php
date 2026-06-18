<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Validation\ValidationException;

class MatterpipeQuota
{
    public function ensureTeamCanCreateWorkspace(Team $team): void
    {
        $this->ensureLimit(
            $team->workspaces()->count(),
            (int) config('matterpipe.quotas.team_workspaces'),
            'This team has reached its workspace limit.',
        );
    }

    public function ensureUserCanCreateProject(User $user): void
    {
        $this->ensureLimit(
            $user->projects()->count(),
            (int) config('matterpipe.quotas.user_projects'),
            'Your account has reached its project limit.',
        );
    }

    public function ensureTeamCanCreateProject(Team $team): void
    {
        $this->ensureLimit(
            $team->projects()->count(),
            (int) config('matterpipe.quotas.team_projects'),
            'This team has reached its project limit.',
        );
    }

    public function ensureWorkspaceCanCreateProject(Workspace $workspace): void
    {
        $this->ensureLimit(
            $workspace->projects()->count(),
            (int) config('matterpipe.quotas.workspace_projects'),
            'This workspace has reached its project limit.',
        );
    }

    public function ensureDeploymentWithinLimits(int $bytes, int $files): void
    {
        $this->ensureLimit($bytes, (int) config('matterpipe.quotas.deployment_bytes'), 'This deployment is too large.');
        $this->ensureLimit($files, (int) config('matterpipe.quotas.deployment_files'), 'This deployment has too many files.');
    }

    public function ensureProjectCanCreateDocument(Project $project): void
    {
        $this->ensureLimit(
            $project->documents()->count(),
            (int) config('matterpipe.quotas.project_documents'),
            'This project has reached its document limit.',
        );
    }

    public function ensureProjectCanStoreFile(Project $project, int $additionalBytes): void
    {
        $storedBytes = (int) $project->files()->sum('size');
        $this->ensureLimit(
            $storedBytes + $additionalBytes,
            (int) config('matterpipe.quotas.project_file_bytes'),
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
