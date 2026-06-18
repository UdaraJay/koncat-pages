<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MagicLoginChallenge;
use App\Services\Auth\MagicLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmailChangeController extends Controller
{
    public function __construct(private MagicLoginService $magicLogin)
    {
        //
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'string', Rule::exists('magic_login_challenges', 'id')],
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $challenge = MagicLoginChallenge::query()->findOrFail($validated['challenge_id']);

        abort_unless(
            $challenge->purpose === MagicLoginChallenge::PURPOSE_EMAIL_CHANGE &&
            $request->user()->is($challenge->user),
            403,
        );

        $challenge = $this->magicLogin->consumeCode($challenge, $validated['code']);

        $request->user()->forceFill([
            'email' => $challenge->email,
            'email_verified_at' => now(),
        ])->save();

        $request->session()->forget('email_change.challenge_id');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email address updated.')]);

        return to_route('profile.edit');
    }
}
