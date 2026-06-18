<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $team_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Project> $projects
 */
#[Fillable(['team_id', 'name', 'slug'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Workspace $workspace) {
            if (empty($workspace->slug)) {
                $workspace->slug = static::generateUniqueSlug($workspace->team_id, $workspace->name);
            }
        });

        static::updating(function (Workspace $workspace) {
            if ($workspace->isDirty('name')) {
                $workspace->slug = static::generateUniqueSlug($workspace->team_id, $workspace->name, $workspace->id);
            }
        });

        static::deleting(function (Workspace $workspace) {
            $workspace->projects()->update(['workspace_id' => null]);
            $workspace->memberships()->delete();
        });
    }

    public static function generateUniqueSlug(string $teamId, string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $suffix = 1;

        while (static::query()
            ->where('team_id', $teamId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->withTrashed()
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsToMany<User, $this, WorkspaceMembership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members', 'workspace_id', 'user_id')
            ->using(WorkspaceMembership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkspaceMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function owner(): ?User
    {
        return $this->members()
            ->wherePivot('role', WorkspaceRole::Owner->value)
            ->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
