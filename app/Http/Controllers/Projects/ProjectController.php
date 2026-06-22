<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectSharePermission;
use App\Http\Controllers\Concerns\BuildsProjectMoveTargets;
use App\Http\Controllers\Concerns\BuildsProjectPayloads;
use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MatterpipeQuota;
use App\Services\ProjectAnalytics;
use App\Services\TeamStoragePaths;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
            ->load(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment.securityScan', 'shares.user', 'shares.sharer'])
            ->loadCount(['deployments', 'shares']);

        Gate::authorize('view', $project);

        $projectAnalytics = $analytics->viewSummaries(collect([$project]));
        $deployments = $project->deployments()
            ->with('securityScan')
            ->latest('deployed_at')
            ->limit(10)
            ->get();

        if (
            $project->currentDeployment instanceof Deployment
            && ! $deployments->contains('id', $project->currentDeployment->id)
        ) {
            $deployments->push($project->currentDeployment);
        }

        return Inertia::render('projects/show', [
            'project' => $this->projectPayload($project, $user, $projectAnalytics[$project->id] ?? $analytics->emptySummary()),
            'deployments' => $deployments
                ->map(fn (Deployment $deployment) => $this->deploymentPayload($deployment))
                ->all(),
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
        Gate::authorize('view', $team);

        $workspace = null;

        if ($validated['workspace_id'] ?? null) {
            $workspace = Workspace::query()->whereKey($validated['workspace_id'])->firstOrFail();
            abort_unless($workspace->team_id === $team->id, 422);
            Gate::authorize('createProject', $workspace);
        } else {
            Gate::authorize('createProject', $team);
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
        $this->authorizeWorkspace($current_team, $workspace, 'createProject');

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
        $this->authorizeProject($current_team, $workspace, $project, 'update');

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
        Gate::authorize('update', $project);

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

        Gate::authorize('move', $project);

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
        Gate::authorize('deploy', $project);

        $project->update(['current_deployment_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project unpublished.')]);

        return back();
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project archived.')]);

        return back();
    }

    public function restore(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->trashed(), 404);
        Gate::authorize('delete', $project);

        $project->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project restored.')]);

        return back();
    }

    public function destroyPermanently(Request $request, Project $project, TeamStoragePaths $paths): RedirectResponse
    {
        abort_unless($project->trashed(), 404);
        abort_unless($project->hosting_team_id !== null, 422);
        Gate::authorize('delete', $project);

        $request->validate([
            'name' => ['required', 'string', Rule::in([$project->name])],
        ]);

        $projectDirectory = $paths->projectDirectory($project->hosting_team_id, $project->id);

        if (! Storage::disk((string) config('matterpipe.storage_disk'))->deleteDirectory($projectDirectory)) {
            throw ValidationException::withMessages([
                'project' => __('Project files could not be deleted. Please try again.'),
            ]);
        }

        DB::transaction(function () use ($project): void {
            $project->update(['current_deployment_id' => null]);
            $project->forceDelete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project permanently deleted.')]);

        return to_route('dashboard', ['status' => 'archived']);
    }

    public function destroy(Request $request, Team $current_team, Workspace $workspace, Project $project): RedirectResponse
    {
        $this->authorizeProject($current_team, $workspace, $project, 'delete');

        $request->validate([
            'name' => ['required', 'string', Rule::in([$project->name])],
        ]);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function deploymentPayload(Deployment $deployment): array
    {
        return [
            'id' => $deployment->id,
            'fileCount' => $deployment->file_count,
            'totalBytes' => $deployment->total_bytes,
            'deployedAt' => $deployment->deployed_at->toISOString(),
            'securityScan' => $deployment->securityScanSummary(),
        ];
    }

    protected function authorizeWorkspace(Team $team, Workspace $workspace, string $ability): void
    {
        abort_unless($workspace->team_id === $team->id, 403);

        Gate::authorize($ability, $workspace);
    }

    protected function authorizeProject(Team $team, Workspace $workspace, Project $project, string $ability): void
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        abort_unless($workspace->team_id === $team->id, 403);

        Gate::authorize($ability, $project);
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
        Gate::authorize('view', $team);

        $workspace = null;

        if ($validated['workspace_id'] ?? null) {
            $workspace = Workspace::query()->whereKey($validated['workspace_id'])->firstOrFail();
            abort_unless($workspace->team_id === $team->id, 422);
            Gate::authorize('createProject', $workspace);
        } else {
            Gate::authorize('createProject', $team);
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
