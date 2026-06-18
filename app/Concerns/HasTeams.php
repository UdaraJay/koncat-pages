<?php

namespace App\Concerns;

use App\Data\TeamPermissions;
use App\Data\UserTeam;
use App\Data\WorkspacePermissions;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Enums\WorkspacePermission;
use App\Enums\WorkspaceRole;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

trait HasTeams
{
    /**
     * Get all of the teams the user belongs to.
     *
     * @return BelongsToMany<Team, $this, Membership, 'pivot'>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all of the teams the user owns.
     *
     * @return HasManyThrough<Team, Membership, $this>
     */
    public function ownedTeams(): HasManyThrough
    {
        return $this->hasManyThrough(
            Team::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'team_id',
        )->where('team_members.role', TeamRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the user's current team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Get the user's personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->teams()
            ->where('is_personal', true)
            ->first();
    }

    /**
     * Switch to the given team.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->update(['current_team_id' => $team->id]);
        $this->setRelation('currentTeam', $team);

        URL::defaults(['current_team' => $team->slug]);

        return true;
    }

    /**
     * Determine if the user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Determine if the given team is the user's current team.
     */
    public function isCurrentTeam(Team $team): bool
    {
        return $this->current_team_id === $team->id;
    }

    /**
     * Determine if the user is the owner of the given team.
     */
    public function ownsTeam(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::Owner;
    }

    /**
     * Get the user's role on the given team.
     */
    public function teamRole(Team $team): ?TeamRole
    {
        return $this->teamMemberships()
            ->where('team_id', $team->id)
            ->first()
            ?->role;
    }

    /**
     * Get the user's teams as a collection of UserTeam objects.
     *
     * @return Collection<int, UserTeam>
     */
    public function toUserTeams(bool $includeCurrent = false): Collection
    {
        return $this->teams()
            ->get()
            ->map(fn (Team $team) => ! $includeCurrent && $this->isCurrentTeam($team) ? null : $this->toUserTeam($team))
            ->filter()
            ->values();
    }

    /**
     * Get the user's team as a UserTeam object.
     */
    public function toUserTeam(Team $team): UserTeam
    {
        $role = $this->teamRole($team);

        return new UserTeam(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            subdomain: $team->subdomain,
            isPersonal: $team->is_personal,
            role: $role?->value,
            roleLabel: $role?->label(),
            isCurrent: $this->isCurrentTeam($team),
        );
    }

    /**
     * Get the standard permissions for a team as a TeamPermissions object.
     */
    public function toTeamPermissions(Team $team): TeamPermissions
    {
        $role = $this->teamRole($team);

        return new TeamPermissions(
            canUpdateTeam: $role?->hasPermission(TeamPermission::UpdateTeam) ?? false,
            canDeleteTeam: $role?->hasPermission(TeamPermission::DeleteTeam) ?? false,
            canAddMember: $role?->hasPermission(TeamPermission::AddMember) ?? false,
            canUpdateMember: $role?->hasPermission(TeamPermission::UpdateMember) ?? false,
            canRemoveMember: $role?->hasPermission(TeamPermission::RemoveMember) ?? false,
            canCreateInvitation: $role?->hasPermission(TeamPermission::CreateInvitation) ?? false,
            canCancelInvitation: $role?->hasPermission(TeamPermission::CancelInvitation) ?? false,
        );
    }

    public function fallbackTeam(?Team $excluding = null): ?Team
    {
        return $this->teams()
            ->when($excluding, fn ($query) => $query->where('teams.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(teams.name)')
            ->first();
    }

    /**
     * Determine if the user has the given permission on the team.
     */
    public function hasTeamPermission(Team $team, TeamPermission $permission): bool
    {
        return $this->teamRole($team)?->hasPermission($permission) ?? false;
    }

    public function belongsToWorkspace(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspaces.id', $workspace->id)->exists();
    }

    public function workspaceRole(Workspace $workspace): ?WorkspaceRole
    {
        return $this->workspaces()
            ->where('workspaces.id', $workspace->id)
            ->first()
            ?->pivot
            ?->role;
    }

    public function canManageTeamWorkspaces(Team $team): bool
    {
        $role = $this->teamRole($team);

        return $role === TeamRole::Owner || $role === TeamRole::Admin;
    }

    public function hasWorkspacePermission(Workspace $workspace, WorkspacePermission $permission): bool
    {
        if (! $this->belongsToTeam($workspace->team)) {
            return false;
        }

        if ($this->canManageTeamWorkspaces($workspace->team)) {
            return true;
        }

        return $this->workspaceRole($workspace)?->hasPermission($permission) ?? false;
    }

    public function canCreateTeamProject(Team $team): bool
    {
        return $this->canManageTeamWorkspaces($team);
    }

    public function canCreateWorkspaceProject(Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($workspace, WorkspacePermission::CreateProject);
    }

    public function canAccessProject(Project $project): bool
    {
        if ($project->owner_type === User::class) {
            return $project->owner_id === $this->id;
        }

        if ($project->owner_type !== Team::class) {
            return false;
        }

        if ($project->workspace_id !== null) {
            $workspace = $project->workspace;

            return $workspace !== null
                && ($this->canManageTeamWorkspaces($workspace->team) || $this->belongsToWorkspace($workspace));
        }

        $team = $project->owner;

        return $team instanceof Team && $this->belongsToTeam($team);
    }

    public function canUpdateProject(Project $project): bool
    {
        return $this->canManageProject($project, WorkspacePermission::UpdateProject);
    }

    public function canDeleteProject(Project $project): bool
    {
        return $this->canManageProject($project, WorkspacePermission::DeleteProject);
    }

    public function canDeployProject(Project $project): bool
    {
        return $this->canManageProject($project, WorkspacePermission::DeployProject);
    }

    public function canAccessHostedProject(Project $project): bool
    {
        return $this->canAccessProject($project);
    }

    protected function canManageProject(Project $project, WorkspacePermission $workspacePermission): bool
    {
        if ($project->owner_type === User::class) {
            return $project->owner_id === $this->id;
        }

        if ($project->owner_type !== Team::class) {
            return false;
        }

        if ($project->workspace_id !== null) {
            return $project->workspace !== null
                && $this->hasWorkspacePermission($project->workspace, $workspacePermission);
        }

        $team = $project->owner;

        return $team instanceof Team && $this->canManageTeamWorkspaces($team);
    }

    public function toWorkspacePermissions(Workspace $workspace): WorkspacePermissions
    {
        return new WorkspacePermissions(
            canUpdateWorkspace: $this->hasWorkspacePermission($workspace, WorkspacePermission::UpdateWorkspace),
            canDeleteWorkspace: $this->hasWorkspacePermission($workspace, WorkspacePermission::DeleteWorkspace),
            canAddMember: $this->hasWorkspacePermission($workspace, WorkspacePermission::AddMember),
            canUpdateMember: $this->hasWorkspacePermission($workspace, WorkspacePermission::UpdateMember),
            canRemoveMember: $this->hasWorkspacePermission($workspace, WorkspacePermission::RemoveMember),
            canCreateProject: $this->hasWorkspacePermission($workspace, WorkspacePermission::CreateProject),
            canUpdateProject: $this->hasWorkspacePermission($workspace, WorkspacePermission::UpdateProject),
            canDeleteProject: $this->hasWorkspacePermission($workspace, WorkspacePermission::DeleteProject),
            canDeployProject: $this->hasWorkspacePermission($workspace, WorkspacePermission::DeployProject),
        );
    }
}
