<?php

namespace App\Http\Controllers\Hosted\Concerns;

use App\Models\Project;
use Illuminate\Http\Request;

trait ResolvesHostedProject
{
    protected function hostedProject(Request $request, string $team, string $project): Project
    {
        $hostedProject = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $project)
            ->firstOrFail();

        abort_unless($request->user()?->canAccessHostedProject($hostedProject), 403);

        return $hostedProject;
    }
}
