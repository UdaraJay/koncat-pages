<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\DeleteTeamRequest;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Http\Requests\Teams\UpdateTeamBrandingRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TeamSettingsController extends Controller
{
    /**
     * Show the current team's general settings.
     */
    public function general(Request $request, Team $current_team): Response
    {
        Gate::authorize('view', $current_team);

        return Inertia::render('team-settings/general', [
            'team' => $this->teamPayload($current_team),
            'permissions' => $request->user()->toTeamPermissions($current_team),
        ]);
    }

    /**
     * Show the current team's member settings.
     */
    public function members(Request $request, Team $current_team): Response
    {
        Gate::authorize('view', $current_team);

        return Inertia::render('team-settings/members', [
            'team' => $this->teamPayload($current_team),
            'members' => $current_team->members()->get()->map(function (User $member) {
                /** @var Membership $membership */
                $membership = $member->getRelation('pivot');

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar ?? null,
                    'role' => $membership->role->value,
                    'role_label' => $membership->role->label(),
                ];
            }),
            'invitations' => $current_team->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'permissions' => $request->user()->toTeamPermissions($current_team),
            'availableRoles' => TeamRole::assignable(),
        ]);
    }

    /**
     * Show the current team's branding settings.
     */
    public function branding(Request $request, Team $current_team): Response
    {
        Gate::authorize('view', $current_team);

        return Inertia::render('team-settings/branding', [
            'team' => $this->teamPayload($current_team),
            'permissions' => $request->user()->toTeamPermissions($current_team),
        ]);
    }

    /**
     * Update the current team's general settings.
     */
    public function update(SaveTeamRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('update', $current_team);

        $team = DB::transaction(function () use ($request, $current_team) {
            $team = Team::whereKey($current_team->id)->lockForUpdate()->firstOrFail();

            $team->update([
                'name' => $request->validated('name'),
                'subdomain' => $request->validated('subdomain'),
            ]);

            return $team;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('team-settings.general', ['current_team' => $team]);
    }

    /**
     * Update the current team's branding settings.
     */
    public function updateBranding(UpdateTeamBrandingRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('update', $current_team);

        $uploadedLogoPath = null;

        if ($request->hasFile('logo')) {
            $uploadedLogoPath = $request->file('logo')->store("team-branding/{$current_team->id}", 'public');
        }

        try {
            $previousLogoPath = DB::transaction(function () use ($request, $current_team, $uploadedLogoPath) {
                $team = Team::whereKey($current_team->id)->lockForUpdate()->firstOrFail();
                $previousLogoPath = $team->brand_logo_path;

                $team->update([
                    'brand_logo_path' => $uploadedLogoPath
                        ?? ($request->boolean('remove_logo') ? null : $team->brand_logo_path),
                    'brand_background_color' => $request->validated('brand_background_color'),
                    'brand_foreground_color' => $request->validated('brand_foreground_color'),
                ]);

                return $previousLogoPath;
            });
        } catch (\Throwable $exception) {
            if ($uploadedLogoPath) {
                Storage::disk('public')->delete($uploadedLogoPath);
            }

            throw $exception;
        }

        if ($previousLogoPath && $previousLogoPath !== $uploadedLogoPath && ($uploadedLogoPath || $request->boolean('remove_logo'))) {
            Storage::disk('public')->delete($previousLogoPath);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return to_route('team-settings.branding', ['current_team' => $current_team]);
    }

    /**
     * Delete the current team.
     */
    public function destroy(DeleteTeamRequest $request, Team $current_team): RedirectResponse
    {
        $user = $request->user();
        $fallbackTeam = $user->isCurrentTeam($current_team)
            ? $user->fallbackTeam($current_team)
            : null;

        DB::transaction(function () use ($user, $current_team) {
            User::where('current_team_id', $current_team->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchTeam($affectedUser->personalTeam()));

            $current_team->projects()->each(fn ($project) => $project->delete());
            $current_team->workspaces()->each(fn ($workspace) => $workspace->delete());
            $current_team->invitations()->delete();
            $current_team->memberships()->delete();
            if ($current_team->brand_logo_path) {
                Storage::disk('public')->delete($current_team->brand_logo_path);
            }
            $current_team->delete();
        });

        if ($fallbackTeam) {
            $user->switchTeam($fallbackTeam);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('teams.index');
    }

    /**
     * Get the shared team settings payload.
     *
     * @return array<string, mixed>
     */
    protected function teamPayload(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'subdomain' => $team->subdomain,
            'hostingDomain' => config('matterpipe.hosting_domain'),
            'hostingScheme' => config('matterpipe.hosting_scheme'),
            'isPersonal' => $team->is_personal,
            'brandLogoUrl' => $team->brandLogoUrl(),
            'brandBackgroundColor' => $team->brand_background_color,
            'brandForegroundColor' => $team->brand_foreground_color,
        ];
    }
}
