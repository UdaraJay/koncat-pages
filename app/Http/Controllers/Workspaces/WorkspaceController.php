<?php

namespace App\Http\Controllers\Workspaces;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Concerns\BuildsProjectMoveTargets;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\MatterpipeLimitResolver;
use App\Services\MatterpipeQuota;
use App\Services\MatterpipeRuntimeTokens;
use App\Services\ProjectAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    use BuildsProjectMoveTargets;

    public function index(Request $request, Team $current_team, MatterpipeLimitResolver $limits): Response
    {
        $user = $request->user();
        Gate::authorize('view', $current_team);

        $workspaces = $current_team->workspaces()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->filter(fn (Workspace $workspace) => Gate::forUser($user)->allows('view', $workspace))
            ->map(fn (Workspace $workspace) => $this->workspacePayload($workspace, $user));

        return Inertia::render('workspaces/index', [
            'workspaces' => $workspaces,
            'canCreateWorkspace' => Gate::forUser($user)->allows('createWorkspace', $current_team),
            'quota' => [
                'workspaces' => $current_team->workspaces()->count(),
                'maxWorkspaces' => $limits->teamWorkspaces($current_team),
            ],
        ]);
    }

    public function store(Request $request, Team $current_team, MatterpipeQuota $quota): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('createWorkspace', $current_team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $quota->ensureTeamCanCreateWorkspace($current_team);

        $workspace = DB::transaction(function () use ($current_team, $user, $validated) {
            $workspace = $current_team->workspaces()->create(['name' => $validated['name']]);

            $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

            return $workspace;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace created.')]);

        return to_route('workspaces.show', [$current_team, $workspace]);
    }

    public function show(Request $request, Team $current_team, Workspace $workspace, MatterpipeLimitResolver $limits, ProjectAnalytics $analytics): Response
    {
        $user = $request->user();
        abort_unless($workspace->team_id === $current_team->id, 403);
        Gate::authorize('view', $workspace);
        $projects = $workspace->projects()
            ->with(['currentDeployment.securityScan', 'hostingTeam', 'shares.user'])
            ->withCount(['deployments', 'shares'])
            ->orderBy('name')
            ->get();
        $projectAnalytics = $analytics->viewSummaries($projects);

        return Inertia::render('workspaces/show', [
            'workspace' => $this->workspacePayload($workspace, $user),
            'members' => $workspace->members()->orderBy('name')->get()->map(function (User $member) {
                /** @var WorkspaceMembership $membership */
                $membership = $member->getRelation('pivot');

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar ?? null,
                    'role' => $membership->role->value,
                    'role_label' => $membership->role->label(),
                ];
            }),
            'projects' => $projects
                ->map(function ($project) use ($analytics, $current_team, $projectAnalytics, $user, $workspace) {
                    $analyticsSummary = $projectAnalytics[$project->id] ?? $analytics->emptySummary();

                    if (! $user->canManageProjectShares($project)) {
                        $analyticsSummary['sharedUsers'] = [];
                    }

                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'slug' => $project->slug,
                        'description' => $project->description,
                        'url' => $project->url(),
                        'previewUrl' => app(MatterpipeRuntimeTokens::class)->renderUrl($project, $user),
                        'ownerType' => 'team',
                        'ownerName' => $current_team->name,
                        'team' => [
                            'id' => $current_team->id,
                            'name' => $current_team->name,
                            'slug' => $current_team->slug,
                        ],
                        'workspace' => [
                            'id' => $workspace->id,
                            'name' => $workspace->name,
                            'slug' => $workspace->slug,
                        ],
                        'deploymentsCount' => $project->deployments_count,
                        'sharesCount' => $project->shares_count,
                        'analytics' => $analyticsSummary,
                        'currentDeployment' => $project->currentDeployment ? [
                            'id' => $project->currentDeployment->id,
                            'fileCount' => $project->currentDeployment->file_count,
                            'totalBytes' => $project->currentDeployment->total_bytes,
                            'deployedAt' => $project->currentDeployment->deployed_at->toISOString(),
                            'securityScan' => $project->currentDeployment->securityScanSummary(),
                        ] : null,
                    ];
                }),
            'permissions' => $user->toWorkspacePermissions($workspace),
            'availableRoles' => WorkspaceRole::assignable(),
            'teamMembers' => $current_team->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email']),
            'moveTargets' => $this->projectMoveTargets($user),
            'quota' => [
                'projects' => $workspace->projects()->withTrashed()->count(),
                'maxProjects' => $limits->workspaceProjects($workspace),
            ],
        ]);
    }

    public function update(Request $request, Team $current_team, Workspace $workspace): RedirectResponse
    {
        abort_unless($workspace->team_id === $current_team->id, 403);
        Gate::authorize('update', $workspace);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $workspace->update(['name' => $validated['name']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        return to_route('workspaces.show', [$current_team, $workspace->fresh()]);
    }

    public function destroy(Request $request, Team $current_team, Workspace $workspace): RedirectResponse
    {
        abort_unless($workspace->team_id === $current_team->id, 403);
        Gate::authorize('delete', $workspace);

        $request->validate([
            'name' => ['required', 'string', Rule::in([$workspace->name])],
        ]);

        $workspace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace deleted.')]);

        return to_route('workspaces.index', $current_team);
    }

    /**
     * @return array<string, mixed>
     */
    protected function workspacePayload(Workspace $workspace, User $user): array
    {
        $role = $user->workspaceRole($workspace);

        return [
            'id' => $workspace->id,
            'teamId' => $workspace->team_id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'role' => $role?->value,
            'roleLabel' => $role?->label(),
            'projectsCount' => $workspace->projects_count ?? $workspace->projects()->count(),
        ];
    }
}
