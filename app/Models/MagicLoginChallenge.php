<?php

namespace App\Models;

use Database\Factories\MagicLoginChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $email
 * @property string|null $user_id
 * @property string $purpose
 * @property string $token_hash
 * @property string $code_hash
 * @property bool $remember
 * @property array<string, mixed>|null $metadata
 * @property Carbon $sent_at
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property int $attempts
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
#[Fillable([
    'email',
    'user_id',
    'purpose',
    'token_hash',
    'code_hash',
    'remember',
    'metadata',
    'sent_at',
    'expires_at',
    'consumed_at',
    'attempts',
    'ip_address',
    'user_agent',
])]
class MagicLoginChallenge extends Model
{
    /** @use HasFactory<MagicLoginChallengeFactory> */
    use HasFactory, HasUlids;

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_CONFIRM_ACCESS = 'confirm-access';

    public const PURPOSE_EMAIL_CHANGE = 'email-change';

    public const EXPIRES_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'remember' => 'boolean',
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
