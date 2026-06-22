<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $current_team, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $current_team);

        abort_if($current_team->owner()?->is($user), 403, __('The team owner role cannot be changed.'));

        $newRole = TeamRole::from($request->validated('role'));

        $current_team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('team-settings.members.index', ['current_team' => $current_team]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $current_team, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $current_team);

        abort_if($current_team->owner()?->is($user), 403, __('The team owner cannot be removed.'));

        $current_team->workspaces()
            ->each(fn ($workspace) => $workspace->members()->detach($user));

        $current_team->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentTeam($current_team)) {
            $user->switchTeam($user->personalTeam());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('team-settings.members.index', ['current_team' => $current_team]);
    }
}
