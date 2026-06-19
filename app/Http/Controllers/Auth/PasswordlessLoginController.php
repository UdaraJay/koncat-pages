<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Auth\Concerns\HandlesMagicLoginResults;
use App\Http\Controllers\Controller;
use App\Models\MagicLoginChallenge;
use App\Services\Auth\MagicLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordlessLoginController extends Controller
{
    use HandlesMagicLoginResults;

    public function __construct(private MagicLoginService $magicLogin)
    {
        //
    }

    public function show(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
            'teamInvitation' => $this->teamInvitationContext($request->query('invitation')),
            'projectShare' => $this->projectShareContext($request->query('project_share')),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'invitation' => ['nullable', 'string'],
            'project_share' => ['nullable', 'string'],
        ]);

        $email = $this->magicLogin->normalizeEmail($validated['email']);
        $invitation = $this->invitationForCode($validated['invitation'] ?? null);
        $projectShare = $this->projectShareForCode($validated['project_share'] ?? null);

        if ($invitation && $this->magicLogin->normalizeEmail($invitation->email) !== $email) {
            throw ValidationException::withMessages([
                'email' => __('This invitation was sent to a different email address.'),
            ]);
        }

        if ($projectShare && $this->magicLogin->normalizeEmail($projectShare->email) !== $email) {
            throw ValidationException::withMessages([
                'email' => __('This project was shared with a different email address.'),
            ]);
        }

        $result = $this->magicLogin->createAndSend(
            email: $email,
            request: $request,
            remember: true,
            metadata: [
                'invitation' => $invitation?->code,
                'project_share' => $projectShare?->code,
            ],
        );

        return to_route('login.magic.check', ['challenge' => $result['challenge']]);
    }

    public function checkEmail(MagicLoginChallenge $challenge): Response
    {
        abort_unless($challenge->purpose === MagicLoginChallenge::PURPOSE_LOGIN, 404);

        return Inertia::render('auth/check-email', [
            'challengeId' => $challenge->id,
            'maskedEmail' => $this->magicLogin->maskEmail($challenge->email),
            'expiresInMinutes' => MagicLoginChallenge::EXPIRES_MINUTES,
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string', Rule::exists('magic_login_challenges', 'id')],
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $challenge = MagicLoginChallenge::query()->findOrFail($validated['challenge_id']);
        abort_unless($challenge->purpose === MagicLoginChallenge::PURPOSE_LOGIN, 404);

        $challenge = $this->magicLogin->consumeCode($challenge, $validated['code']);

        return $this->completeMagicLogin($request, $challenge);
    }

    public function complete(Request $request): Response
    {
        $email = $request->session()->get('magic_login.verified_email');
        $expiresAt = (int) $request->session()->get('magic_login.verified_until', 0);

        abort_unless(is_string($email) && $expiresAt >= now()->unix(), 403);

        return Inertia::render('auth/complete-account', [
            'email' => $email,
            'teamInvitation' => $this->teamInvitationContext($request->session()->get('magic_login.invitation')),
            'projectShare' => $this->projectShareContext($request->session()->get('magic_login.project_share')),
        ]);
    }

    public function storeCompleted(Request $request, CreateTeam $createTeam): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $email = $request->session()->get('magic_login.verified_email');

        if (! is_string($email)) {
            abort(403);
        }

        $user = DB::transaction(function () use ($request, $createTeam, $email) {
            $existingUser = $this->magicLogin->findUserByEmail($email);

            return $existingUser ?: $this->createVerifiedUser($request, $createTeam);
        });

        Auth::guard(config('fortify.guard'))->login($user);
        $request->session()->regenerate();
        $request->session()->forget(['magic_login.invitation', 'magic_login.project_share']);

        return $this->redirectAfterMagicLogin($request);
    }
}
