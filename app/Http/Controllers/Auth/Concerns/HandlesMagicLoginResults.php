<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Actions\Teams\CreateTeam;
use App\Models\MagicLoginChallenge;
use App\Models\ProjectShare;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Auth\MagicLoginService;
use App\Services\ProjectShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

trait HandlesMagicLoginResults
{
    protected function completeMagicLogin(Request $request, MagicLoginChallenge $challenge): RedirectResponse
    {
        if ($challenge->purpose === MagicLoginChallenge::PURPOSE_CONFIRM_ACCESS) {
            abort_unless($request->user()?->is($challenge->user), 403);

            $request->session()->passwordConfirmed();

            return redirect()->intended(Fortify::redirects('password-confirmation') ?? route('security.edit'));
        }

        if ($challenge->purpose === MagicLoginChallenge::PURPOSE_EMAIL_CHANGE) {
            abort_unless($request->user()?->is($challenge->user), 403);

            $request->user()->forceFill([
                'email' => $challenge->email,
                'email_verified_at' => now(),
            ])->save();

            app(ProjectShareService::class)->claimPendingForUser($request->user());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Email address updated.')]);

            return to_route('profile.edit');
        }

        $user = $challenge->user ?: app(MagicLoginService::class)->findUserByEmail($challenge->email);

        if (! $user) {
            $request->session()->put('magic_login.verified_email', $challenge->email);
            $request->session()->put('magic_login.verified_until', now()->addMinutes(MagicLoginChallenge::EXPIRES_MINUTES)->unix());
            $request->session()->put('magic_login.invitation', $challenge->metadata['invitation'] ?? null);
            $request->session()->put('magic_login.project_share', $challenge->metadata['project_share'] ?? null);

            return to_route('login.complete');
        }

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?: now()])->save();

        if ($this->requiresTwoFactorChallenge($user)) {
            $this->forgetAuthFlowIntendedUrl($request);

            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $challenge->remember,
            ]);

            return to_route('two-factor.login');
        }

        Auth::guard(config('fortify.guard'))->login($user, $challenge->remember);
        $request->session()->regenerate();

        return $this->redirectAfterMagicLogin($request);
    }

    protected function createVerifiedUser(Request $request, CreateTeam $createTeam): User
    {
        $email = $request->session()->get('magic_login.verified_email');
        $expiresAt = (int) $request->session()->get('magic_login.verified_until', 0);

        abort_unless(is_string($email) && $expiresAt >= now()->unix(), 403);

        $user = User::create([
            'name' => $request->string('name')->trim()->toString(),
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        $createTeam->handlePersonal($user);
        app(ProjectShareService::class)->claimPendingForUser($user);

        $request->session()->forget([
            'magic_login.verified_email',
            'magic_login.verified_until',
        ]);

        return $user;
    }

    protected function invitationForCode(?string $code): ?TeamInvitation
    {
        if (! $code) {
            return null;
        }

        return TeamInvitation::query()
            ->where('code', $code)
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->first();
    }

    protected function projectShareForCode(?string $code): ?ProjectShare
    {
        if (! $code) {
            return null;
        }

        return ProjectShare::query()
            ->with(['project', 'sharer'])
            ->where('code', $code)
            ->first();
    }

    /**
     * @return array{code: string, teamName: string}|null
     */
    protected function teamInvitationContext(?string $code): ?array
    {
        $invitation = $this->invitationForCode($code);

        if (! $invitation) {
            return null;
        }

        return [
            'code' => $invitation->code,
            'teamName' => $invitation->team->name,
        ];
    }

    /**
     * @return array{code: string, projectName: string, sharerName: string}|null
     */
    protected function projectShareContext(?string $code): ?array
    {
        $share = $this->projectShareForCode($code);

        if (! $share) {
            return null;
        }

        return [
            'code' => $share->code,
            'projectName' => $share->project->name,
            'sharerName' => $share->sharer->name,
        ];
    }

    protected function requiresTwoFactorChallenge(User $user): bool
    {
        return $user->two_factor_secret &&
            ! is_null($user->two_factor_confirmed_at) &&
            in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user));
    }

    protected function redirectAfterMagicLogin(Request $request): RedirectResponse
    {
        $this->forgetAuthFlowIntendedUrl($request);

        return redirect()->intended(route('dashboard'));
    }

    protected function forgetAuthFlowIntendedUrl(Request $request): void
    {
        $intended = $request->session()->get('url.intended');

        if (! is_string($intended)) {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        $path = '/'.ltrim($path, '/');

        if ($path === '/login' || str_starts_with($path, '/login/')) {
            $request->session()->forget('url.intended');
        }
    }
}
