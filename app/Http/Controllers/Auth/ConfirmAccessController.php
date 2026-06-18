<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MagicLoginChallenge;
use App\Services\Auth\MagicLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class ConfirmAccessController extends Controller
{
    public function __construct(private MagicLoginService $magicLogin)
    {
        //
    }

    public function show(Request $request): Response
    {
        return Inertia::render('auth/confirm-access', [
            'maskedEmail' => $this->magicLogin->maskEmail($request->user()->email),
            'challengeId' => $request->session()->get('confirm_access.challenge_id'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $result = $this->magicLogin->createAndSend(
            email: $request->user()->email,
            request: $request,
            purpose: MagicLoginChallenge::PURPOSE_CONFIRM_ACCESS,
            user: $request->user(),
        );

        $request->session()->put('confirm_access.challenge_id', $result['challenge']->id);

        return to_route('password.confirm')->with('status', __('Check your email for a confirmation code.'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string', Rule::exists('magic_login_challenges', 'id')],
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $challenge = MagicLoginChallenge::query()->findOrFail($validated['challenge_id']);

        abort_unless(
            $challenge->purpose === MagicLoginChallenge::PURPOSE_CONFIRM_ACCESS &&
            $request->user()->is($challenge->user),
            403,
        );

        $this->magicLogin->consumeCode($challenge, $validated['code']);
        $request->session()->passwordConfirmed();
        $request->session()->forget('confirm_access.challenge_id');

        return redirect()->intended(Fortify::redirects('password-confirmation') ?? route('security.edit'));
    }
}
