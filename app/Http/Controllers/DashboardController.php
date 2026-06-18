<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $teams = $user->teams()
            ->with(['workspaces' => fn ($query) => $query->orderBy('name')])
            ->orderByRaw('LOWER(teams.name)')
            ->get();

        $manageableTeamIds = $teams
            ->filter(fn (Team $team) => $user->canManageTeamWorkspaces($team))
            ->pluck('id');

        $workspaceIds = Workspace::query()
            ->whereIn('team_id', $manageableTeamIds)
            ->orWhereHas('members', fn ($members) => $members->whereKey($user->id))
            ->pluck('id');

        $projects = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment'])
            ->withCount('deployments')
            ->where(function ($query) use ($teams, $user, $workspaceIds) {
                $query
                    ->where(function ($personal) use ($user) {
                        $personal
                            ->where('owner_type', User::class)
                            ->where('owner_id', $user->id);
                    })
                    ->orWhere(function ($teamProjects) use ($teams) {
                        $teamProjects
                            ->where('owner_type', Team::class)
                            ->whereIn('owner_id', $teams->pluck('id'))
                            ->whereNull('workspace_id');
                    })
                    ->orWhereIn('workspace_id', $workspaceIds);
            })
            ->latest('updated_at')
            ->get()
            ->map(fn (Project $project) => $this->projectPayload($project, $user));

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'projects' => $projects,
            'createOptions' => [
                'owners' => $this->ownerOptions($teams, $user),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function projectPayload(Project $project, User $user): array
    {
        $team = $project->workspace?->team;

        if (! $team && $project->owner instanceof Team) {
            $team = $project->owner;
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'description' => $project->description,
            'url' => $project->url(),
            'ownerType' => $project->owner_type === User::class ? 'user' : 'team',
            'ownerName' => $project->owner_type === User::class ? __('Personal') : $team?->name,
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ] : null,
            'workspace' => $project->workspace ? [
                'id' => $project->workspace->id,
                'name' => $project->workspace->name,
                'slug' => $project->workspace->slug,
            ] : null,
            'deploymentsCount' => $project->deployments_count,
            'canDeploy' => $user->canDeployProject($project),
            'createdAt' => $project->created_at?->toISOString(),
            'updatedAt' => $project->updated_at?->toISOString(),
            'currentDeployment' => $project->currentDeployment ? [
                'id' => $project->currentDeployment->id,
                'fileCount' => $project->currentDeployment->file_count,
                'totalBytes' => $project->currentDeployment->total_bytes,
                'deployedAt' => $project->currentDeployment->deployed_at->toISOString(),
            ] : null,
        ];
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, array<string, mixed>>
     */
    protected function ownerOptions(Collection $teams, User $user): array
    {
        $owners = [[
            'type' => 'user',
            'id' => $user->id,
            'name' => __('Personal'),
            'label' => __('Personal'),
            'canCreateProject' => true,
            'workspaces' => [],
        ]];

        return [
            ...$owners,
            ...$teams
                ->reject(fn (Team $team) => $team->is_personal)
                ->map(function (Team $team) use ($user) {
                    $workspaces = $team->workspaces
                        ->filter(fn (Workspace $workspace) => $user->canCreateWorkspaceProject($workspace))
                        ->map(fn (Workspace $workspace) => [
                            'id' => $workspace->id,
                            'name' => $workspace->name,
                        ])
                        ->values()
                        ->all();

                    return [
                        'type' => 'team',
                        'id' => $team->id,
                        'name' => $team->name,
                        'label' => $team->name,
                        'canCreateProject' => $user->canCreateTeamProject($team),
                        'workspaces' => $workspaces,
                    ];
                })
                ->filter(fn (array $owner) => $owner['canCreateProject'] || count($owner['workspaces']) > 0)
                ->values()
                ->all(),
        ];
    }
}
