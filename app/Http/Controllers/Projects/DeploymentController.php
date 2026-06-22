<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Team;
use App\Models\Workspace;
use App\Services\DeploymentPublisher;
use App\Services\MatterpipeLimitResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DeploymentController extends Controller
{
    public function store(Request $request, Team $current_team, Workspace $workspace, Project $project, DeploymentPublisher $publisher, MatterpipeLimitResolver $limits): RedirectResponse
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        abort_unless($workspace->team_id === $current_team->id, 403);
        Gate::authorize('deploy', $project);

        $this->publishFromRequest($request, $project, $publisher, $limits);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deployed.')]);

        return back();
    }

    public function storeGlobal(Request $request, Project $project, DeploymentPublisher $publisher, MatterpipeLimitResolver $limits): RedirectResponse
    {
        Gate::authorize('deploy', $project);

        $this->publishFromRequest($request, $project, $publisher, $limits);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deployed.')]);

        return back();
    }

    public function activate(Request $request, Project $project, Deployment $deployment): RedirectResponse
    {
        abort_unless(! $project->trashed(), 404);
        abort_unless($deployment->project_id === $project->id, 404);
        Gate::authorize('deploy', $project);

        $project->update(['current_deployment_id' => $deployment->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deployment restored.')]);

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
