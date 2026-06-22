<?php

namespace App\Policies;

use App\Enums\ProjectSharePermission;
use App\Enums\TeamPermission;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        if ($this->ownsPersonalProject($user, $project) || $this->hasProjectShare($user, $project)) {
            return true;
        }

        $team = $this->team($project);

        return $team instanceof Team
            && $user->hasTeamPermission($team, TeamPermission::ViewProject);
    }

    /**
     * Determine whether the user can create runtime documents/files for the project.
     */
    public function write(User $user, Project $project): bool
    {
        return $this->update($user, $project)
            || $this->hasProjectShare($user, $project, ProjectSharePermission::Write);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        if ($this->ownsPersonalProject($user, $project)) {
            return true;
        }

        if ($this->hasProjectShare($user, $project, ProjectSharePermission::Write)) {
            return true;
        }

        return $this->canManageTeamProject($user, $project, TeamPermission::UpdateOwnProject, 'updateProject');
    }

    /**
     * Determine whether the user can delete, archive, or restore the project.
     */
    public function delete(User $user, Project $project): bool
    {
        if ($this->ownsPersonalProject($user, $project)) {
            return true;
        }

        return $this->canManageTeamProject($user, $project, TeamPermission::DeleteOwnProject, 'deleteProject');
    }

    /**
     * Determine whether the user can deploy or unpublish the project.
     */
    public function deploy(User $user, Project $project): bool
    {
        if ($this->ownsPersonalProject($user, $project)) {
            return true;
        }

        return $this->canManageTeamProject($user, $project, TeamPermission::DeployOwnProject, 'deployProject');
    }

    /**
     * Determine whether the user can move the project.
     */
    public function move(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can manage project shares.
     */
    public function share(User $user, Project $project): bool
    {
        if ($this->ownsPersonalProject($user, $project)) {
            return true;
        }

        return $this->canManageTeamProject($user, $project, TeamPermission::ShareOwnProject, 'addMember');
    }

    protected function canManageTeamProject(User $user, Project $project, TeamPermission $ownPermission, string $workspaceAbility): bool
    {
        $team = $this->team($project);

        if (! $team instanceof Team || ! $user->belongsToTeam($team)) {
            return false;
        }

        if ($project->workspace && $user->can($workspaceAbility, $project->workspace)) {
            return true;
        }

        if ($user->can('manageWorkspaces', $team)) {
            return true;
        }

        return $project->created_by === $user->id
            && $user->hasTeamPermission($team, $ownPermission);
    }

    protected function ownsPersonalProject(User $user, Project $project): bool
    {
        return $project->owner_type === User::class && $project->owner_id === $user->id;
    }

    protected function team(Project $project): ?Team
    {
        if ($project->workspace) {
            return $project->workspace->team;
        }

        if ($project->owner_type === Team::class && $project->owner instanceof Team) {
            return $project->owner;
        }

        return null;
    }

    protected function hasProjectShare(User $user, Project $project, ?ProjectSharePermission $minimumPermission = null): bool
    {
        return $project->shares()
            ->where(function ($query) use ($user) {
                $query
                    ->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(email) = ?', [strtolower($user->email)]);
            })
            ->when($minimumPermission === ProjectSharePermission::Write, fn ($query) => $query->where('permission', ProjectSharePermission::Write->value))
            ->exists();
    }
}
