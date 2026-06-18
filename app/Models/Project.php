<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property string $id
 * @property string $owner_type
 * @property string $owner_id
 * @property string|null $workspace_id
 * @property string|null $hosting_team_id
 * @property string|null $created_by
 * @property string|null $current_deployment_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|Team $owner
 * @property-read Workspace|null $workspace
 * @property-read Team|null $hostingTeam
 * @property-read User|null $creator
 * @property-read Deployment|null $currentDeployment
 * @property-read Collection<int, Deployment> $deployments
 * @property-read Collection<int, ProjectShare> $shares
 */
#[Fillable(['owner_type', 'owner_id', 'workspace_id', 'hosting_team_id', 'created_by', 'current_deployment_id', 'name', 'slug', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Project $project) {
            if (empty($project->hosting_team_id)) {
                $project->hosting_team_id = $project->resolveHostingTeamId();
            }

            if (empty($project->slug)) {
                $project->slug = static::generateUniqueSlug($project->hosting_team_id);
            }
        });
    }

    public static function generateUniqueSlug(?string $hostingTeamId, ?string $ignoreId = null): string
    {
        do {
            $slug = static::randomProjectPath();
        } while (static::query()
            ->where('hosting_team_id', $hostingTeamId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->withTrashed()
            ->exists());

        return $slug;
    }

    public static function pathIsAvailable(string $hostingTeamId, string $slug, ?string $ignoreId = null): bool
    {
        return ! static::query()
            ->where('hosting_team_id', $hostingTeamId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->withTrashed()
            ->exists();
    }

    protected static function randomProjectPath(): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';

        $prefix = '';

        for ($index = 0; $index < 6; $index++) {
            $prefix .= $letters[random_int(0, strlen($letters) - 1)];
        }

        $suffix = '';

        for ($index = 0; $index < 4; $index++) {
            $suffix .= $digits[random_int(0, strlen($digits) - 1)];
        }

        return "{$prefix}-{$suffix}";
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function hostingTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'hosting_team_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function currentDeployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'current_deployment_id');
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasMany<ProjectShare, $this>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(ProjectShare::class);
    }

    /**
     * @return HasMany<ProjectDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    /**
     * @return HasMany<ProjectFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function hasInheritedAccess(User $user): bool
    {
        return $user->canAccessProjectInherited($this);
    }

    public function url(): string
    {
        $hostingTeam = $this->hostingTeam;

        if (! $hostingTeam instanceof Team) {
            throw new LogicException('Project cannot build a hosted URL without a hosting team.');
        }

        return sprintf(
            '%s://%s.%s/%s',
            config('matterpipe.hosting_scheme'),
            $hostingTeam->subdomain,
            config('matterpipe.hosting_domain'),
            $this->slug,
        );
    }

    public function previewUrl(): string
    {
        return $this->url().'/__matterpipe/render/index.html';
    }

    protected function resolveHostingTeamId(): ?string
    {
        if ($this->workspace_id) {
            return Workspace::query()
                ->whereKey($this->workspace_id)
                ->value('team_id');
        }

        if ($this->owner_type === Team::class) {
            return $this->owner_id;
        }

        if ($this->owner_type === User::class) {
            return User::query()
                ->whereKey($this->owner_id)
                ->first()
                ?->personalTeam()
                ?->id;
        }

        return null;
    }
}
