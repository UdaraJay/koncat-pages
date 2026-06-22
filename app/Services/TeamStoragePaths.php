<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use LogicException;

class TeamStoragePaths
{
    public function teamDirectory(string $teamId): string
    {
        return "teams/{$teamId}";
    }

    public function brandingDirectory(string $teamId): string
    {
        return $this->teamDirectory($teamId).'/branding';
    }

    public function brandingLogoPath(string $teamId, string $filename): string
    {
        return $this->brandingDirectory($teamId).'/'.basename($filename);
    }

    public function projectDirectory(string $teamId, string $projectId): string
    {
        return $this->teamDirectory($teamId)."/projects/{$projectId}";
    }

    public function deploymentDirectory(Project $project, string $deploymentId): string
    {
        return $this->projectDirectory($this->hostingTeamId($project), $project->id)."/deployments/{$deploymentId}";
    }

    public function projectFilesDirectory(Project $project): string
    {
        return $this->projectDirectory($this->hostingTeamId($project), $project->id).'/files';
    }

    public function projectFilePath(Project $project, ProjectFile $file, string $filename): string
    {
        return $this->projectFilesDirectory($project).'/'.$this->projectFileName($file, $filename);
    }

    public function projectFileName(ProjectFile $file, string $filename): string
    {
        return "{$file->id}-".basename($filename);
    }

    protected function hostingTeamId(Project $project): string
    {
        if (! $project->hosting_team_id) {
            throw new LogicException('Project storage paths require a hosting team.');
        }

        return $project->hosting_team_id;
    }
}
