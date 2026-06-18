<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\MagicLoginChallenge;
use App\Services\Auth\MagicLoginService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private MagicLoginService $magicLogin)
    {
        //
    }

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'emailChangeChallengeId' => $request->session()->get('email_change.challenge_id'),
            'pendingEmail' => $request->session()->get('email_change.email'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $newEmail = $this->magicLogin->normalizeEmail($validated['email']);
        $currentEmail = $this->magicLogin->normalizeEmail($user->email);

        $user->forceFill(['name' => $validated['name']])->save();

        if ($newEmail !== $currentEmail) {
            $result = $this->magicLogin->createAndSend(
                email: $newEmail,
                request: $request,
                purpose: MagicLoginChallenge::PURPOSE_EMAIL_CHANGE,
                user: $user,
            );

            $request->session()->put('email_change.challenge_id', $result['challenge']->id);
            $request->session()->put('email_change.email', $newEmail);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Check your new email address to confirm the change.')]);

            return to_route('profile.edit')->with('status', 'email-change-link-sent');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
