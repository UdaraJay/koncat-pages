<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Http\Request;

trait ResolvesUserApiToken
{
    protected function userFromBearerToken(Request $request): ?User
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return null;
        }

        $token = UserApiToken::query()
            ->where('token_hash', UserApiToken::hashToken($plainTextToken))
            ->first();

        if (! $token || $token->isExpired()) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token->user;
    }
}
