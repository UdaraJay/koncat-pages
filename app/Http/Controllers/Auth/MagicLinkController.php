<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesMagicLoginResults;
use App\Http\Controllers\Controller;
use App\Models\MagicLoginChallenge;
use App\Services\Auth\MagicLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkController extends Controller
{
    use HandlesMagicLoginResults;

    public function __construct(private MagicLoginService $magicLogin)
    {
        //
    }

    public function show(MagicLoginChallenge $challenge, string $token): Response
    {
        return Inertia::render('auth/continue-login', [
            'challengeId' => $challenge->id,
            'token' => $token,
            'maskedEmail' => $this->magicLogin->maskEmail($challenge->email),
            'purpose' => $challenge->purpose,
        ]);
    }

    public function consume(Request $request, MagicLoginChallenge $challenge): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $challenge = $this->magicLogin->consumeToken($challenge, $validated['token']);

        return $this->completeMagicLogin($request, $challenge);
    }
}
