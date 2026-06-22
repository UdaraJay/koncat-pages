<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Enums\WorkspacePermission;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view the workspace.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        if (! $user->belongsToTeam($workspace->team)) {
            return false;
        }

        return $user->can('manageWorkspaces', $workspace->team)
            || $user->hasTeamPermission($workspace->team, TeamPermission::ViewWorkspace)
            || $user->belongsToWorkspace($workspace);
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::UpdateWorkspace);
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::DeleteWorkspace);
    }

    /**
     * Determine whether the user can add a member to the workspace.
     */
    public function addMember(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::AddMember);
    }

    /**
     * Determine whether the user can update a workspace member.
     */
    public function updateMember(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::UpdateMember);
    }

    /**
     * Determine whether the user can remove a workspace member.
     */
    public function removeMember(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::RemoveMember);
    }

    /**
     * Determine whether the user can create projects in the workspace.
     */
    public function createProject(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::CreateProject);
    }

    /**
     * Determine whether the user can update projects in the workspace.
     */
    public function updateProject(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::UpdateProject);
    }

    /**
     * Determine whether the user can delete projects in the workspace.
     */
    public function deleteProject(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::DeleteProject);
    }

    /**
     * Determine whether the user can deploy projects in the workspace.
     */
    public function deployProject(User $user, Workspace $workspace): bool
    {
        return $this->hasWorkspacePermission($user, $workspace, WorkspacePermission::DeployProject);
    }

    protected function hasWorkspacePermission(User $user, Workspace $workspace, WorkspacePermission $permission): bool
    {
        if (! $user->belongsToTeam($workspace->team)) {
            return false;
        }

        if ($user->can('manageWorkspaces', $workspace->team)) {
            return true;
        }

        return $user->workspaceRole($workspace)?->hasPermission($permission) ?? false;
    }
}
