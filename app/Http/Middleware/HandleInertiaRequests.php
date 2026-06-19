<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'currentTeamProjects' => function () use ($user) {
                if (! $user || ! $user->currentTeam) {
                    return [];
                }

                return $this->currentTeamProjects($user);
            },
            'currentTeamWorkspaces' => fn () => $user?->currentTeam
                ? $user->currentTeam
                    ->workspaces()
                    ->withCount('projects')
                    ->when(! $user->canManageTeamWorkspaces($user->currentTeam), function ($query) use ($user) {
                        $query->whereHas('members', fn ($members) => $members->whereKey($user->id));
                    })
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($workspace) => [
                        'id' => $workspace->id,
                        'teamId' => $workspace->team_id,
                        'name' => $workspace->name,
                        'slug' => $workspace->slug,
                        'role' => $user->workspaceRole($workspace)?->value,
                        'roleLabel' => $user->workspaceRole($workspace)?->label(),
                        'projectsCount' => $workspace->projects_count,
                    ])
                : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function currentTeamProjects(User $user): array
    {
        $team = $user->currentTeam;

        if (! $team instanceof Team) {
            return [];
        }

        return Project::query()
            ->with(['workspace', 'hostingTeam'])
            ->withCount('deployments')
            ->when($team->is_personal, function ($query) use ($user) {
                $query
                    ->where('owner_type', User::class)
                    ->where('owner_id', $user->id);
            }, function ($query) use ($team, $user) {
                $query
                    ->where('owner_type', Team::class)
                    ->where('owner_id', $team->id)
                    ->where(function ($projects) use ($team, $user) {
                        $projects
                            ->whereNull('workspace_id')
                            ->orWhereHas('workspace', function ($workspaces) use ($team, $user) {
                                $workspaces->where('team_id', $team->id);

                                if (! $user->canManageTeamWorkspaces($team)) {
                                    $workspaces->whereHas('members', fn ($members) => $members->whereKey($user->id));
                                }
                            });
                    });
            })
            ->latest('updated_at')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'url' => $project->url(),
                'previewUrl' => $project->previewUrl(),
                'ownerType' => $team->is_personal ? 'user' : 'team',
                'ownerName' => $team->is_personal ? __('Personal') : $team->name,
                'team' => $team->is_personal ? null : [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                ],
                'workspace' => $project->workspace ? [
                    'id' => $project->workspace->id,
                    'name' => $project->workspace->name,
                    'slug' => $project->workspace->slug,
                ] : null,
                'deploymentsCount' => $project->deployments_count,
            ])
            ->values()
            ->all();
    }
}
