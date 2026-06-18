<?php

namespace App\Http\Controllers\Projects;

use App\Enums\WorkspacePermission;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MatterpipeQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProjectController extends Controller
{
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
