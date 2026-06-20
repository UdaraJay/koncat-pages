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
 * @property string|null $deployment_id
 * @property string $user_id
 * @property string $status
 * @property string|null $highest_severity
 * @property int $risk_score
 * @property string $scanner
 * @property string $scanner_version
 * @property array<int, array<string, mixed>>|null $findings
 * @property array<string, mixed>|null $metadata
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Deployment|null $deployment
 * @property-read User $user
 */
#[Fillable(['project_id', 'deployment_id', 'user_id', 'status', 'highest_severity', 'risk_score', 'scanner', 'scanner_version', 'findings', 'metadata', 'started_at', 'finished_at'])]
class DeploymentSecurityScan extends Model
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
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
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
            'findings' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
