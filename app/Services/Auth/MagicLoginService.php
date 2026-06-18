<?php

namespace App\Services\Auth;

use App\Models\MagicLoginChallenge;
use App\Models\User;
use App\Notifications\Auth\MagicLoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MagicLoginService
{
    /**
     * Create and email a single-use login challenge.
     *
     * @param  array<string, mixed>|null  $metadata
     * @return array{challenge: MagicLoginChallenge, token: string, code: string}
     */
    public function createAndSend(
        string $email,
        Request $request,
        string $purpose = MagicLoginChallenge::PURPOSE_LOGIN,
        bool $remember = false,
        ?User $user = null,
        ?array $metadata = null,
    ): array {
        $email = $this->normalizeEmail($email);
        $user ??= $this->findUserByEmail($email);
        $token = Str::random(64);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $challenge = DB::transaction(function () use ($email, $user, $purpose, $token, $code, $remember, $metadata, $request) {
            MagicLoginChallenge::query()
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return MagicLoginChallenge::create([
                'email' => $email,
                'user_id' => $user?->id,
                'purpose' => $purpose,
                'token_hash' => $this->hashSecret($token),
                'code_hash' => $this->hashSecret($code),
                'remember' => $remember,
                'metadata' => $metadata,
                'sent_at' => now(),
                'expires_at' => now()->addMinutes(MagicLoginChallenge::EXPIRES_MINUTES),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            ]);
        });

        Notification::route('mail', $email)
            ->notify(new MagicLoginNotification($challenge, $token, $code));

        return compact('challenge', 'token', 'code');
    }

    public function consumeToken(MagicLoginChallenge $challenge, string $token): MagicLoginChallenge
    {
        $challenge = $challenge->fresh() ?? $challenge;

        if (! $challenge->isPending() || ! hash_equals($challenge->token_hash, $this->hashSecret($token))) {
            throw ValidationException::withMessages([
                'token' => __('This sign-in link is invalid or expired.'),
            ]);
        }

        return $this->consume($challenge);
    }

    public function consumeCode(MagicLoginChallenge $challenge, string $code): MagicLoginChallenge
    {
        $challenge = $challenge->fresh() ?? $challenge;

        if (! $challenge->isPending()) {
            throw ValidationException::withMessages([
                'code' => __('This code is invalid or expired.'),
            ]);
        }

        if ($challenge->attempts >= MagicLoginChallenge::MAX_ATTEMPTS) {
            $challenge->forceFill(['consumed_at' => now()])->save();

            throw ValidationException::withMessages([
                'code' => __('Too many attempts. Request a new code.'),
            ]);
        }

        if (! hash_equals($challenge->code_hash, $this->hashSecret($code))) {
            $challenge->increment('attempts');

            throw ValidationException::withMessages([
                'code' => __('The code you entered is incorrect.'),
            ]);
        }

        return $this->consume($challenge);
    }

    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [$this->normalizeEmail($email)])
            ->first();
    }

    public function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($local === '' || $domain === '') {
            return $email;
        }

        return Str::substr($local, 0, 1).str_repeat('*', max(1, Str::length($local) - 1)).'@'.$domain;
    }

    protected function consume(MagicLoginChallenge $challenge): MagicLoginChallenge
    {
        $challenge->forceFill(['consumed_at' => now()])->save();

        return $challenge->fresh() ?? $challenge;
    }

    protected function hashSecret(string $secret): string
    {
        return hash_hmac('sha256', $secret, config('app.key'));
    }
}
