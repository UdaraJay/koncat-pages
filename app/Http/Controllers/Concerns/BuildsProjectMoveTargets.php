<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;

trait BuildsProjectMoveTargets
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function projectMoveTargets(User $user): array
    {
        $personalTeam = $user->personalTeam();
        $targets = [];

        if ($personalTeam instanceof Team) {
            $targets[] = [
                'type' => 'user',
                'id' => $user->id,
                'teamId' => $personalTeam->id,
                'name' => __('Personal'),
                'label' => __('Personal'),
                'isPersonal' => true,
                'canCreateProject' => true,
                'workspaces' => [],
            ];
        }

        $user->teams()
            ->where('is_personal', false)
            ->orderByRaw('LOWER(teams.name)')
            ->get()
            ->each(function (Team $team) use (&$targets, $user): void {
                $workspaces = $team->workspaces()
                    ->orderBy('name')
                    ->get()
                    ->filter(fn (Workspace $workspace) => $user->canCreateWorkspaceProject($workspace))
                    ->map(fn (Workspace $workspace) => [
                        'id' => $workspace->id,
                        'name' => $workspace->name,
                    ])
                    ->values()
                    ->all();

                $canCreateProject = $user->canCreateTeamProject($team);

                if (! $canCreateProject && count($workspaces) === 0) {
                    return;
                }

                $targets[] = [
                    'type' => 'team',
                    'id' => $team->id,
                    'teamId' => $team->id,
                    'name' => $team->name,
                    'label' => $team->name,
                    'isPersonal' => false,
                    'canCreateProject' => $canCreateProject,
                    'workspaces' => $workspaces,
                ];
            });

        return $targets;
    }
}
