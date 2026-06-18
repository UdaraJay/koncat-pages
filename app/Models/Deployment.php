<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $project_id
 * @property string|null $user_id
 * @property string $disk
 * @property string $path
 * @property string|null $original_filename
 * @property array<string, mixed>|null $manifest
 * @property int $file_count
 * @property int $total_bytes
 * @property Carbon $deployed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'user_id', 'disk', 'path', 'original_filename', 'manifest', 'file_count', 'total_bytes', 'deployed_at'])]
class Deployment extends Model
{
    use HasUlids;

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'deployed_at' => 'datetime',
        ];
    }
}
