<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatterpipeIdentityController extends Controller
{
    public function __invoke(Request $request, string $team, string $project): JsonResponse
    {
        $hostedProject = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $project)
            ->firstOrFail();

        abort_unless($request->user()?->canAccessHostedProject($hostedProject), 403);

        $user = $request->user();
        $team = $hostedProject->workspace?->team;

        if (! $team && $hostedProject->owner instanceof Team) {
            $team = $hostedProject->owner;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ] : null,
            'workspace' => $hostedProject->workspace ? [
                'id' => $hostedProject->workspace->id,
                'name' => $hostedProject->workspace->name,
                'slug' => $hostedProject->workspace->slug,
            ] : null,
            'project' => [
                'id' => $hostedProject->id,
                'name' => $hostedProject->name,
                'slug' => $hostedProject->slug,
            ],
        ]);
    }
}
