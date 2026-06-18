<?php

namespace App\Http\Middleware;

use App\Models\UserApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMCPRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = UserApiToken::query()
            ->where('token_hash', UserApiToken::hashToken($plainTextToken))
            ->first();

        if (! $token || $token->isExpired()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::guard()->setUser($token->user);

        return $next($request);
    }
}
