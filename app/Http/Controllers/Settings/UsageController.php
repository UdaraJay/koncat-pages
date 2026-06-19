<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MatterpipeLimitResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsageController extends Controller
{
    public function index(Request $request, MatterpipeLimitResolver $limits): Response
    {
        /** @var User $user */
        $user = $request->user();
        $currentTeam = $user->currentTeam;

        return Inertia::render('settings/usage', [
            'usage' => [
                'account' => [
                    'projects' => [
                        'used' => $user->projects()->withTrashed()->count(),
                        'limit' => $limits->userProjects($user),
                    ],
                ],
                'team' => $currentTeam ? $this->teamUsage($user, $currentTeam, $limits) : null,
                'deploymentLimits' => [
                    'files' => $limits->deploymentFiles($user),
                    'bytes' => $limits->deploymentBytes($user),
                    'fileBytes' => $limits->deploymentFileBytes($user),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function teamUsage(User $user, Team $team, MatterpipeLimitResolver $limits): array
    {
        $canSeeTeamTotals = $user->canManageTeamWorkspaces($team);
        $visibleWorkspaces = $team->workspaces()
            ->withCount([
                'projects as projects_count' => fn ($query) => $query->withTrashed(),
            ])
            ->when(! $canSeeTeamTotals, function (Builder $query) use ($user): void {
                $query->whereHas('members', fn (Builder $members) => $members->whereKey($user->id));
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'projects' => [
                    'used' => $workspace->projects_count,
                    'limit' => $limits->workspaceProjects($workspace),
                ],
            ])
            ->values()
            ->all();

        $usage = [
            'id' => $team->id,
            'name' => $team->name,
            'isPersonal' => $team->is_personal,
            'canSeeTeamTotals' => $canSeeTeamTotals,
            'visibleWorkspaces' => $visibleWorkspaces,
        ];

        if ($canSeeTeamTotals) {
            $usage['projects'] = [
                'used' => $team->projects()->withTrashed()->count(),
                'limit' => $limits->teamProjects($team),
            ];
            $usage['workspaces'] = [
                'used' => $team->workspaces()->count(),
                'limit' => $limits->teamWorkspaces($team),
            ];
        }

        return $usage;
    }
}
