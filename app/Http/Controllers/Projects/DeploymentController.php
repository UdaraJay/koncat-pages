<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Models\Workspace;
use App\Services\DeploymentPublisher;
use App\Services\MatterpipeLimitResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeploymentController extends Controller
{
    public function store(Request $request, Team $current_team, Workspace $workspace, Project $project, DeploymentPublisher $publisher, MatterpipeLimitResolver $limits): RedirectResponse
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        abort_unless($workspace->team_id === $current_team->id, 403);
        abort_unless($request->user()->canDeployProject($project), 403);

        $this->publishFromRequest($request, $project, $publisher, $limits);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deployed.')]);

        return back();
    }

    public function storeGlobal(Request $request, Project $project, DeploymentPublisher $publisher, MatterpipeLimitResolver $limits): RedirectResponse
    {
        abort_unless($request->user()->canDeployProject($project), 403);

        $this->publishFromRequest($request, $project, $publisher, $limits);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deployed.')]);

        return back();
    }

    protected function publishFromRequest(Request $request, Project $project, DeploymentPublisher $publisher, MatterpipeLimitResolver $limits): void
    {
        $maxArchiveBytes = $limits->deploymentBytes($project);
        $archiveRules = ['required', 'file', 'mimes:zip'];

        if ($maxArchiveBytes > 0) {
            $archiveRules[] = 'max:'.ceil($maxArchiveBytes / 1024);
        }

        $validated = $request->validate([
            'archive' => $archiveRules,
        ], [
            'archive.max' => 'This deployment is too large.',
        ]);

        $publisher->publish($project, $validated['archive'], $request->user());
    }
}
