<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/api-tokens', [
            'tokens' => $request->user()->apiTokens()
                ->latest()
                ->get()
                ->map(fn (UserApiToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'lastUsedAt' => $token->last_used_at?->toISOString(),
                    'expiresAt' => $token->expires_at?->toISOString(),
                    'createdAt' => $token->created_at->toISOString(),
                ]),
            'plainTextToken' => session('plainTextToken'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $plainTextToken = UserApiToken::makePlainTextToken();

        $request->user()->apiTokens()->create([
            'name' => $validated['name'],
            'token_hash' => UserApiToken::hashToken($plainTextToken),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token created.')]);

        return back()->with('plainTextToken', $plainTextToken);
    }

    public function destroy(Request $request, UserApiToken $token): RedirectResponse
    {
        abort_unless($token->user_id === $request->user()->id, 403);

        $token->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token revoked.')]);

        return back();
    }
}
