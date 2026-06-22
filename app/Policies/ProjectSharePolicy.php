<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\User;

class ProjectSharePolicy
{
    /**
     * Determine whether the user can create shares for the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->can('share', $project);
    }

    /**
     * Determine whether the user can update the share.
     */
    public function update(User $user, ProjectShare $share): bool
    {
        return $user->can('share', $share->project);
    }

    /**
     * Determine whether the user can delete the share.
     */
    public function delete(User $user, ProjectShare $share): bool
    {
        return $user->can('share', $share->project);
    }
}
