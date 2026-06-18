<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;

class ConnectedApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $tokens = Passport::token()
            ->newQuery()
            ->with('client')
            ->where('user_id', $request->user()->id)
            ->where('revoked', false)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        return Inertia::render('settings/connected-applications', [
            'applications' => $tokens
                ->filter(fn (Token $token): bool => $token->client instanceof Client)
                ->groupBy('client_id')
                ->map(function ($clientTokens): array {
                    /** @var Token $latestToken */
                    $latestToken = $clientTokens->sortByDesc('created_at')->first();
                    /** @var Client $client */
                    $client = $latestToken->client;

                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'redirectUris' => $client->redirect_uris ?? [],
                        'scopes' => $clientTokens
                            ->flatMap(fn (Token $token): array => $token->scopes ?? [])
                            ->unique()
                            ->values()
                            ->all(),
                        'tokenCount' => $clientTokens->count(),
                        'connectedAt' => $clientTokens->min('created_at')?->toISOString(),
                        'lastAuthorizedAt' => $latestToken->created_at?->toISOString(),
                        'expiresAt' => $clientTokens
                            ->pluck('expires_at')
                            ->filter()
                            ->sort()
                            ->first()?->toISOString(),
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    public function destroy(Request $request, Client $application): RedirectResponse
    {
        $tokens = Passport::token()
            ->newQuery()
            ->where('user_id', $request->user()->id)
            ->where('client_id', $application->id)
            ->get();

        foreach ($tokens as $token) {
            $token->refreshToken()->update(['revoked' => true]);
            $token->revoke();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application disconnected.')]);

        return back();
    }
}
