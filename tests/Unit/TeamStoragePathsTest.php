<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\TeamStoragePaths;
use LogicException;
use PHPUnit\Framework\TestCase;

class TeamStoragePathsTest extends TestCase
{
    public function test_it_builds_team_scoped_storage_paths(): void
    {
        $paths = new TeamStoragePaths;
        $project = new Project([
            'hosting_team_id' => 'team_123',
        ]);
        $project->id = 'project_456';
        $file = new ProjectFile;
        $file->id = 'file_789';

        $this->assertSame('teams/team_123', $paths->teamDirectory('team_123'));
        $this->assertSame('teams/team_123/branding', $paths->brandingDirectory('team_123'));
        $this->assertSame('teams/team_123/branding/logo.png', $paths->brandingLogoPath('team_123', 'logo.png'));
        $this->assertSame('teams/team_123/projects/project_456/deployments/deploy_abc', $paths->deploymentDirectory($project, 'deploy_abc'));
        $this->assertSame('teams/team_123/projects/project_456/files/file_789-asset.png', $paths->projectFilePath($project, $file, 'asset.png'));
    }

    public function test_project_paths_require_a_hosting_team(): void
    {
        $this->expectException(LogicException::class);

        $project = new Project;
        $project->id = 'project_456';

        (new TeamStoragePaths)->deploymentDirectory($project, 'deploy_abc');
    }
}
