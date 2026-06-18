<?php

namespace App\Http\Controllers\Workspaces;

use App\Enums\WorkspacePermission;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\MatterpipeQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        $user = $request->user();
        abort_unless($user->belongsToTeam($current_team), 403);

        $workspaces = $current_team->workspaces()
            ->withCount('projects')
            ->when(! $user->canManageTeamWorkspaces($current_team), function ($query) use ($user) {
                $query->whereHas('members', fn ($members) => $members->whereKey($user->id));
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace) => $this->workspacePayload($workspace, $user));

        return Inertia::render('workspaces/index', [
            'workspaces' => $workspaces,
            'canCreateWorkspace' => $user->canManageTeamWorkspaces($current_team),
            'quota' => [
                'workspaces' => $current_team->workspaces()->count(),
                'maxWorkspaces' => config('matterpipe.quotas.team_workspaces'),
            ],
        ]);
    }

    public function store(Request $request, Team $current_team, MatterpipeQuota $quota): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageTeamWorkspaces($current_team), 403);

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

    public function show(Request $request, Team $current_team, Workspace $workspace): Response
    {
        $user = $request->user();
        $this->authorizeWorkspaceView($user, $current_team, $workspace);

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
            'projects' => $workspace->projects()
                ->with(['currentDeployment', 'hostingTeam'])
                ->withCount('deployments')
                ->orderBy('name')
                ->get()
                ->map(fn ($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'description' => $project->description,
                    'url' => $project->url(),
                    'deploymentsCount' => $project->deployments_count,
                    'currentDeployment' => $project->currentDeployment ? [
                        'id' => $project->currentDeployment->id,
                        'fileCount' => $project->currentDeployment->file_count,
                        'totalBytes' => $project->currentDeployment->total_bytes,
                        'deployedAt' => $project->currentDeployment->deployed_at->toISOString(),
                    ] : null,
                ]),
            'permissions' => $user->toWorkspacePermissions($workspace),
            'availableRoles' => WorkspaceRole::assignable(),
            'teamMembers' => $current_team->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email']),
            'quota' => [
                'projects' => $workspace->projects()->count(),
                'maxProjects' => config('matterpipe.quotas.workspace_projects'),
            ],
        ]);
    }

    public function update(Request $request, Team $current_team, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeWorkspacePermission($user, $current_team, $workspace, WorkspacePermission::UpdateWorkspace);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $workspace->update(['name' => $validated['name']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        return to_route('workspaces.show', [$current_team, $workspace->fresh()]);
    }

    public function destroy(Request $request, Team $current_team, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeWorkspacePermission($user, $current_team, $workspace, WorkspacePermission::DeleteWorkspace);

        $request->validate([
            'name' => ['required', 'string', Rule::in([$workspace->name])],
        ]);

        $workspace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace deleted.')]);

        return to_route('workspaces.index', $current_team);
    }

    protected function authorizeWorkspaceView(User $user, Team $team, Workspace $workspace): void
    {
        abort_unless($workspace->team_id === $team->id && $user->belongsToTeam($team), 403);
        abort_unless($user->canManageTeamWorkspaces($team) || $user->belongsToWorkspace($workspace), 403);
    }

    protected function authorizeWorkspacePermission(User $user, Team $team, Workspace $workspace, WorkspacePermission $permission): void
    {
        abort_unless($workspace->team_id === $team->id && $user->hasWorkspacePermission($workspace, $permission), 403);
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
