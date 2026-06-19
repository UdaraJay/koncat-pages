<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;

class MatterpipeLimitResolver
{
    public function userProjects(User $user): int
    {
        return $this->limit('user_projects');
    }

    public function teamProjects(Team $team): int
    {
        return $this->limit('team_projects');
    }

    public function teamWorkspaces(Team $team): int
    {
        return $this->limit('team_workspaces');
    }

    public function workspaceProjects(Workspace $workspace): int
    {
        return $this->limit('workspace_projects');
    }

    public function deploymentFiles(Project|User $subject): int
    {
        return $this->limit('deployment_files');
    }

    public function deploymentBytes(Project|User $subject): int
    {
        return $this->limit('deployment_bytes');
    }

    public function deploymentFileBytes(Project|User $subject): int
    {
        return $this->limit('deployment_file_bytes');
    }

    public function projectFiles(Project $project): int
    {
        return $this->limit('project_files');
    }

    public function projectFileUploadBytes(Project $project): int
    {
        return $this->limit('project_file_upload_bytes');
    }

    public function projectFileBytes(Project $project): int
    {
        return $this->limit('project_file_bytes');
    }

    public function projectDocuments(Project $project): int
    {
        return $this->limit('project_documents');
    }

    protected function limit(string $key): int
    {
        return (int) config("matterpipe.quotas.{$key}", 0);
    }
}
