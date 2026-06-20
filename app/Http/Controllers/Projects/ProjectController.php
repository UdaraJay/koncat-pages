<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectSharePermission;
use App\Enums\WorkspacePermission;
use App\Http\Controllers\Concerns\BuildsProjectMoveTargets;
use App\Http\Controllers\Concerns\BuildsProjectPayloads;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MatterpipeQuota;
use App\Services\ProjectAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use BuildsProjectMoveTargets, BuildsProjectPayloads;

    public function show(Request $request, Project $project, ProjectAnalytics $analytics): Response
    {
        $user = $request->user();

        $project
            ->load(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment', 'shares.user', 'shares.sharer'])
            ->loadCount(['deployments', 'shares']);

        abort_unless($user->canAccessProject($project), 403);

        $projectAnalytics = $analytics->viewSummaries(collect([$project]));

        return Inertia::render('projects/show', [
            'project' => $this->projectPayload($project, $user, $projectAnalytics[$project->id] ?? $analytics->emptySummary()),
            'projectSharePermissions' => ProjectSharePermission::options(),
            'moveTargets' => $this->projectMoveTargets($user),
        ]);
    }

    public function store(Request $request, MatterpipeQuota $quota): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'owner_type' => ['required', 'string', Rule::in(['user', 'team'])],
            'team_id' => ['nullable', 'string', Rule::exists('teams', 'id')],
            'workspace_id' => ['nullable', 'string', Rule::exists('workspaces', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash:ascii'],
        ]);

        if ($validated['owner_type'] === 'user') {
            $quota->ensureUserCanCreateProject($user);
            $hostingTeamId = $user->personalTeam()?->id;
            abort_unless($hostingTeamId !== null, 422);
            $this->ensureProjectPathAvailable($validated['slug'] ?? null, $hostingTeamId);

            $user->projects()->create([
                'hosting_team_id' => $hostingTeamId,
                'created_by' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'slug' => $validated['slug'] ?? null,
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

            return to_route('dashboard');
        }

        $team = Team::query()->whereKey($validated['team_id'] ?? null)->firstOrFail();
        abort_unless($user->belongsToTeam($team), 403);

        $workspace = null;

        if ($validated['workspace_id'] ?? null) {
            $workspace = Workspace::query()->whereKey($validated['workspace_id'])->firstOrFail();
            abort_unless($workspace->team_id === $team->id, 422);
            abort_unless($user->canCreateWorkspaceProject($workspace), 403);
        } else {
            abort_unless($user->canCreateTeamProject($team), 403);
        }

        $this->ensureProjectPathAvailable($validated['slug'] ?? null, $team->id);
        $quota->ensureTeamCanCreateProject($team);

        if ($workspace) {
            $quota->ensureWorkspaceCanCreateProject($workspace);
        }

        $team->projects()->create([
            'workspace_id' => $workspace?->id,
            'hosting_team_id' => $team->id,
            'created_by' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => $validated['slug'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('dashboard');
    }

    public function storeInWorkspace(Request $request, Team $current_team, Workspace $workspace, MatterpipeQuota $quota): RedirectResponse
    {
        $this->authorizeRequest($request->user(), $current_team, $workspace, WorkspacePermission::CreateProject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash:ascii'],
        ]);

        $this->ensureProjectPathAvailable($validated['slug'] ?? null, $current_team->id);
        $quota->ensureTeamCanCreateProject($current_team);
        $quota->ensureWorkspaceCanCreateProject($workspace);

        $current_team->projects()->create([
            ...$validated,
            'workspace_id' => $workspace->id,
            'hosting_team_id' => $current_team->id,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('workspaces.show', [$current_team, $workspace]);
    }

    public function update(Request $request, Team $current_team, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorizeProject($request->user(), $current_team, $workspace, $project, WorkspacePermission::UpdateProject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash:ascii'],
        ]);

        $this->ensureProjectPathAvailable($validated['slug'], $current_team->id, $project);
        $project->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return back();
    }

    public function updateDetails(Request $request, Project $project): RedirectResponse
    {
        abort_unless(! $project->trashed(), 404);
        abort_unless($request->user()->canUpdateProject($project), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $project->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return back();
    }

    public function move(Request $request, Project $project, MatterpipeQuota $quota): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->canDeleteProject($project), 403);

        $validated = $request->validate([
            'owner_type' => ['required', 'string', Rule::in(['user', 'team'])],
            'team_id' => ['nullable', 'string', Rule::exists('teams', 'id')],
            'workspace_id' => ['nullable', 'string', Rule::exists('workspaces', 'id')],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash:ascii'],
        ]);

        [$ownerType, $ownerId, $workspace, $hostingTeam] = $this->resolveMoveDestination($user, $validated);

        $this->ensureProjectPathAvailable($validated['slug'], $hostingTeam->id, $project);
        $this->ensureMoveWithinQuota($project, $user, $ownerType, $ownerId, $hostingTeam, $workspace, $quota);

        DB::transaction(function () use ($project, $validated, $ownerType, $ownerId, $workspace, $hostingTeam): void {
            $project->update([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'workspace_id' => $workspace?->id,
                'hosting_team_id' => $hostingTeam->id,
                'slug' => $validated['slug'],
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project moved.')]);

        return back();
    }

    public function unpublish(Request $request, Project $project): RedirectResponse
    {
        abort_unless($request->user()->canDeployProject($project), 403);

        $project->update(['current_deployment_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project unpublished.')]);

        return back();
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        abort_unless($request->user()->canDeleteProject($project), 403);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project archived.')]);

        return back();
    }

    public function restore(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->trashed(), 404);
        abort_unless($request->user()->canDeleteProject($project), 403);

        $project->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project restored.')]);

        return back();
    }

    public function destroy(Request $request, Team $current_team, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorizeProject($request->user(), $current_team, $workspace, $project, WorkspacePermission::DeleteProject);

        $request->validate([
            'name' => ['required', 'string', Rule::in([$project->name])],
        ]);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return back();
    }

    protected function authorizeRequest(User $user, Team $team, Workspace $workspace, WorkspacePermission $permission): void
    {
        abort_unless($workspace->team_id === $team->id && $user->hasWorkspacePermission($workspace, $permission), 403);
    }

    protected function authorizeProject(User $user, Team $team, Workspace $workspace, Project $project, WorkspacePermission $permission): void
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        $this->authorizeRequest($user, $team, $workspace, $permission);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: class-string<User|Team>, 1: string, 2: Workspace|null, 3: Team}
     */
    protected function resolveMoveDestination(User $user, array $validated): array
    {
        if ($validated['owner_type'] === 'user') {
            $personalTeam = $user->personalTeam();

            abort_unless($personalTeam instanceof Team, 422);

            return [User::class, $user->id, null, $personalTeam];
        }

        $team = Team::query()->whereKey($validated['team_id'] ?? null)->firstOrFail();
        abort_unless($user->belongsToTeam($team), 403);

        $workspace = null;

        if ($validated['workspace_id'] ?? null) {
            $workspace = Workspace::query()->whereKey($validated['workspace_id'])->firstOrFail();
            abort_unless($workspace->team_id === $team->id, 422);
            abort_unless($user->canCreateWorkspaceProject($workspace), 403);
        } else {
            abort_unless($user->canCreateTeamProject($team), 403);
        }

        return [Team::class, $team->id, $workspace, $team];
    }

    /**
     * @param  class-string<User|Team>  $ownerType
     */
    protected function ensureMoveWithinQuota(Project $project, User $user, string $ownerType, string $ownerId, Team $hostingTeam, ?Workspace $workspace, MatterpipeQuota $quota): void
    {
        if ($ownerType === User::class && ($project->owner_type !== User::class || $project->owner_id !== $user->id)) {
            $quota->ensureUserCanCreateProject($user);
        }

        if ($ownerType === Team::class && ($project->owner_type !== Team::class || $project->owner_id !== $ownerId)) {
            $quota->ensureTeamCanCreateProject($hostingTeam);
        }

        if ($workspace instanceof Workspace && $project->workspace_id !== $workspace->id) {
            $quota->ensureWorkspaceCanCreateProject($workspace);
        }
    }

    protected function ensureProjectPathAvailable(?string $slug, string $hostingTeamId, ?Project $ignore = null): void
    {
        if (! $slug || Project::pathIsAvailable($hostingTeamId, $slug, $ignore?->id)) {
            return;
        }

        throw ValidationException::withMessages([
            'slug' => __('The path has already been taken for this team.'),
        ]);
    }
}
