<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\Team;
use App\Models\User;
use App\Services\MatterpipeRuntimeTokens;

trait BuildsProjectPayloads
{
    /**
     * @return array<string, mixed>
     */
    protected function projectPayload(Project $project, User $user, array $analytics): array
    {
        $team = $project->workspace?->team;
        $canManageShares = $user->canManageProjectShares($project);

        if (! $team && $project->owner instanceof Team) {
            $team = $project->owner;
        }

        if (! $canManageShares) {
            $analytics['sharedUsers'] = [];
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'description' => $project->description,
            'url' => $project->url(),
            'previewUrl' => app(MatterpipeRuntimeTokens::class)->renderUrl($project, $user),
            'ownerType' => $project->owner_type === User::class ? 'user' : 'team',
            'ownerName' => $project->owner_type === User::class ? __('Personal') : $team?->name,
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ] : null,
            'workspace' => $project->workspace ? [
                'id' => $project->workspace->id,
                'name' => $project->workspace->name,
                'slug' => $project->workspace->slug,
            ] : null,
            'deploymentsCount' => $project->deployments_count,
            'sharesCount' => $project->shares_count,
            'analytics' => $analytics,
            'canUpdate' => $user->canUpdateProject($project) && ! $project->trashed(),
            'canDeploy' => $user->canDeployProject($project),
            'canUnpublish' => $project->current_deployment_id !== null && $user->canDeployProject($project) && ! $project->trashed(),
            'canArchive' => $user->canDeleteProject($project) && ! $project->trashed(),
            'canRestore' => $user->canDeleteProject($project) && $project->trashed(),
            'canMove' => $user->canDeleteProject($project) && ! $project->trashed(),
            'canManageShares' => $canManageShares,
            'sharePermission' => $this->shareForUser($project, $user)?->permission->value,
            'sharePermissionLabel' => $this->shareForUser($project, $user)?->permission->label(),
            'sharedByName' => $this->shareForUser($project, $user)?->sharer?->name,
            'shares' => $canManageShares
                ? $project->shares
                    ->sortBy(fn (ProjectShare $share) => strtolower($share->email))
                    ->map(fn (ProjectShare $share) => [
                        'code' => $share->code,
                        'email' => $share->email,
                        'name' => $share->user?->name,
                        'permission' => $share->permission->value,
                        'permissionLabel' => $share->permission->label(),
                        'pending' => $share->user_id === null,
                    ])
                    ->values()
                    ->all()
                : [],
            'createdAt' => $project->created_at?->toISOString(),
            'updatedAt' => $project->updated_at?->toISOString(),
            'deletedAt' => $project->deleted_at?->toISOString(),
            'currentDeployment' => $project->currentDeployment ? [
                'id' => $project->currentDeployment->id,
                'fileCount' => $project->currentDeployment->file_count,
                'totalBytes' => $project->currentDeployment->total_bytes,
                'deployedAt' => $project->currentDeployment->deployed_at->toISOString(),
            ] : null,
        ];
    }

    protected function shareForUser(Project $project, User $user): ?ProjectShare
    {
        $email = strtolower($user->email);

        return $project->shares
            ->first(fn (ProjectShare $share) => $share->user_id === $user->id || strtolower($share->email) === $email);
    }
}
