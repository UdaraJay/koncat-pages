<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Models\Workspace;
use App\Services\DeploymentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeploymentController extends Controller
{
    public function store(Request $request, Team $current_team, Workspace $workspace, Project $project, DeploymentPublisher $publisher): RedirectResponse
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        abort_unless($workspace->team_id === $current_team->id, 403);
        abort_unless($request->user()->canDeployProject($project), 403);

        $this->publishFromRequest($request, $project, $publisher);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deployed.')]);

        return back();
    }

    public function storeGlobal(Request $request, Project $project, DeploymentPublisher $publisher): RedirectResponse
    {
        abort_unless($request->user()->canDeployProject($project), 403);

        $this->publishFromRequest($request, $project, $publisher);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deployed.')]);

        return back();
    }

    protected function publishFromRequest(Request $request, Project $project, DeploymentPublisher $publisher): void
    {
        $validated = $request->validate([
            'archive' => ['required', 'file', 'mimes:zip', 'max:'.ceil(((int) config('matterpipe.quotas.deployment_bytes')) / 1024)],
        ]);

        $publisher->publish($project, $validated['archive'], $request->user());
    }
}
