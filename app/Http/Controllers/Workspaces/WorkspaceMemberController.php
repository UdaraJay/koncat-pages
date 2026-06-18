<?php

namespace App\Http\Controllers\Workspaces;

use App\Enums\WorkspacePermission;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WorkspaceMemberController extends Controller
{
    public function store(Request $request, Team $current_team, Workspace $workspace): RedirectResponse
    {
        $this->authorizeRequest($request->user(), $current_team, $workspace, WorkspacePermission::AddMember);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', Rule::in(array_column(WorkspaceRole::assignable(), 'value'))],
        ]);

        $member = $current_team->members()
            ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->first();

        abort_unless($member !== null, 422, 'Workspace members must already belong to the team.');

        $workspace->members()->syncWithoutDetaching([
            $member->id => ['role' => $validated['role']],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace member added.')]);

        return back();
    }

    public function update(Request $request, Team $current_team, Workspace $workspace, User $user): RedirectResponse
    {
        $this->authorizeRequest($request->user(), $current_team, $workspace, WorkspacePermission::UpdateMember);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(array_column(WorkspaceRole::assignable(), 'value'))],
        ]);

        abort_if($workspace->owner()?->is($user), 403);

        $workspace->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace member updated.')]);

        return back();
    }

    public function destroy(Request $request, Team $current_team, Workspace $workspace, User $user): RedirectResponse
    {
        $this->authorizeRequest($request->user(), $current_team, $workspace, WorkspacePermission::RemoveMember);

        abort_if($workspace->owner()?->is($user), 403);

        $workspace->members()->detach($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace member removed.')]);

        return back();
    }

    protected function authorizeRequest(User $user, Team $team, Workspace $workspace, WorkspacePermission $permission): void
    {
        abort_unless($workspace->team_id === $team->id && $user->hasWorkspacePermission($workspace, $permission), 403);
    }
}
