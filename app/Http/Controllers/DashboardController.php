<?php

namespace App\Http\Controllers;

use App\Enums\ProjectSharePermission;
use App\Http\Controllers\Concerns\BuildsProjectMoveTargets;
use App\Http\Controllers\Concerns\BuildsProjectPayloads;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectAnalytics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use BuildsProjectMoveTargets, BuildsProjectPayloads;

    public function __invoke(Request $request, ProjectAnalytics $analytics): Response
    {
        $user = $request->user();
        $currentTeam = $user->currentTeam;

        abort_unless($currentTeam instanceof Team, 403);

        $email = strtolower($user->email);
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:active,archived,all'],
            'sort' => ['nullable', 'string', 'in:updated_desc,created_desc,name_asc'],
        ]);
        $status = $validated['status'] ?? 'active';
        $sort = $validated['sort'] ?? 'updated_desc';

        $pendingInvitations = $currentTeam->is_personal
            ? TeamInvitation::query()
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
                ])
            : collect();

        $projectModels = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment', 'shares.user', 'shares.sharer'])
            ->withCount(['deployments', 'shares'])
            ->when($currentTeam->is_personal, function ($query) use ($user) {
                $query
                    ->where('owner_type', User::class)
                    ->where('owner_id', $user->id);
            }, function ($query) use ($currentTeam, $user) {
                $workspaceIds = $this->accessibleWorkspaceIds($currentTeam, $user);

                $query
                    ->where('owner_type', Team::class)
                    ->where('owner_id', $currentTeam->id)
                    ->where(function ($teamProjects) use ($workspaceIds) {
                        $teamProjects
                            ->whereNull('workspace_id')
                            ->orWhereIn('workspace_id', $workspaceIds);
                    });
            })
            ->tap(fn ($query) => $this->applyProjectFilters($query, $status, $sort))
            ->get();
        $projectAnalytics = $analytics->viewSummaries($projectModels);
        $projects = $projectModels
            ->map(fn (Project $project) => $this->projectPayload($project, $user, $projectAnalytics[$project->id] ?? $analytics->emptySummary()));

        $sharedProjectModels = $currentTeam->is_personal
            ? Project::query()
                ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment', 'shares.user', 'shares.sharer'])
                ->withCount(['deployments', 'shares'])
                ->whereHas('shares', fn ($shares) => $shares
                    ->where(fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->orWhereRaw('LOWER(email) = ?', [$email])))
                ->tap(fn ($query) => $this->applyProjectFilters($query, $status, $sort))
                ->get()
                ->reject(fn (Project $project) => $user->canAccessProjectInherited($project))
            : collect();
        $sharedProjectAnalytics = $analytics->viewSummaries($sharedProjectModels);
        $sharedProjects = $sharedProjectModels
            ->map(fn (Project $project) => $this->projectPayload($project, $user, $sharedProjectAnalytics[$project->id] ?? $analytics->emptySummary()));

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'projects' => $projects,
            'sharedProjects' => $sharedProjects->values(),
            'projectSharePermissions' => ProjectSharePermission::options(),
            'projectFilters' => [
                'status' => $status,
                'sort' => $sort,
            ],
            'homeScope' => $this->homeScope($currentTeam, $status),
            'createOptions' => [
                'owners' => $this->ownerOptions($currentTeam, $user),
            ],
            'moveTargets' => $this->projectMoveTargets($user),
        ]);
    }

    protected function applyProjectFilters(Builder $query, string $status, string $sort): void
    {
        $query
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when($status === 'all', fn ($query) => $query->withTrashed())
            ->when($sort === 'updated_desc', fn ($query) => $query->latest('updated_at'))
            ->when($sort === 'created_desc', fn ($query) => $query->latest('created_at'))
            ->when($sort === 'name_asc', fn ($query) => $query->orderByRaw('LOWER(name)'));
    }

    protected function accessibleWorkspaceIds(Team $team, User $user): array
    {
        return Workspace::query()
            ->where('team_id', $team->id)
            ->when(! $user->canManageTeamWorkspaces($team), function ($query) use ($user) {
                $query->whereHas('members', fn ($members) => $members->whereKey($user->id));
            })
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function homeScope(Team $team, string $status): array
    {
        return [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'isPersonal' => $team->is_personal,
            ],
            'projectLabel' => $team->is_personal ? __('My projects') : __('Team projects'),
            'emptyTitle' => $this->emptyProjectsTitle($status),
            'emptyText' => $this->emptyProjectsText($team, $status),
        ];
    }

    protected function emptyProjectsTitle(string $status): string
    {
        if ($status === 'archived') {
            return __('No archived projects');
        }

        if ($status === 'all') {
            return __('No projects found');
        }

        return __('No projects yet');
    }

    protected function emptyProjectsText(Team $team, string $status): string
    {
        if ($status === 'archived') {
            return __('Archived projects will appear here after you archive them from a project card.');
        }

        if ($status === 'all') {
            return __('Try a different filter, or deploy a project from your agent.');
        }

        if (! $team->is_personal) {
            return __('Projects created in this team or your workspaces will appear here.');
        }

        return __('Set up the MCP server above, then ask your agent to deploy a project.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function ownerOptions(Team $currentTeam, User $user): array
    {
        if ($currentTeam->is_personal) {
            return [[
                'type' => 'user',
                'id' => $user->id,
                'name' => __('Personal'),
                'label' => __('Personal'),
                'canCreateProject' => true,
                'workspaces' => [],
            ]];
        }

        $workspaces = $currentTeam
            ->workspaces()
            ->orderBy('name')
            ->get()
            ->filter(fn (Workspace $workspace) => $user->canCreateWorkspaceProject($workspace))
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ])
            ->values()
            ->all();

        $owner = [
            'type' => 'team',
            'id' => $currentTeam->id,
            'name' => $currentTeam->name,
            'label' => $currentTeam->name,
            'canCreateProject' => $user->canCreateTeamProject($currentTeam),
            'workspaces' => $workspaces,
        ];

        return ($owner['canCreateProject'] || count($owner['workspaces']) > 0)
            ? [$owner]
            : [];
    }
}
