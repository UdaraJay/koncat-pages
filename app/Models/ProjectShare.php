<?php

namespace App\Models;

use App\Enums\ProjectSharePermission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $project_id
 * @property string $email
 * @property string|null $user_id
 * @property ProjectSharePermission $permission
 * @property string $shared_by
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read User|null $user
 * @property-read User $sharer
 */
#[Fillable(['project_id', 'email', 'user_id', 'permission', 'shared_by'])]
class ProjectShare extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectShareFactory> */
    use HasFactory, HasUlids;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProjectShare $share) {
            if (empty($share->code)) {
                $share->code = Str::random(64);
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    protected function casts(): array
    {
        return [
            'permission' => ProjectSharePermission::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
