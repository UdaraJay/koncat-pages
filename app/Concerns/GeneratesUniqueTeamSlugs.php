<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait GeneratesUniqueTeamSlugs
{
    /**
     * Generate a unique slug for the team.
     */
    protected static function generateUniqueTeamSlug(string $name, ?string $excludeId = null): string
    {
        return static::generateUniqueTeamValue('slug', $name, $excludeId);
    }

    protected static function generateUniqueTeamSubdomain(string $name, ?string $excludeId = null): string
    {
        return static::generateUniqueTeamValue('subdomain', $name, $excludeId, 63);
    }

    protected static function generateUniqueTeamValue(string $column, string $name, ?string $excludeId = null, int $maxLength = 255): string
    {
        $defaultSlug = Str::slug($name);
        $defaultSlug = $defaultSlug === '' ? 'team' : $defaultSlug;
        $defaultSlug = trim(Str::limit($defaultSlug, $maxLength, ''), '-');

        $query = static::withTrashed()
            ->where(function ($query) use ($column, $defaultSlug) {
                $query->where($column, $defaultSlug)
                    ->orWhere($column, 'like', $defaultSlug.'-%');
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSlugs = $query->pluck($column);

        $maxSuffix = $existingSlugs
            ->map(function (string $slug) use ($defaultSlug): ?int {
                if ($slug === $defaultSlug) {
                    return 0;
                } elseif (preg_match('/^'.preg_quote($defaultSlug, '/').'-(\d+)$/', $slug, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter(fn (?int $suffix) => $suffix !== null)
            ->max() ?? 0;

        if ($existingSlugs->isEmpty()) {
            return $defaultSlug;
        }

        $suffix = '-'.($maxSuffix + 1);

        return trim(Str::limit($defaultSlug, $maxLength - strlen($suffix), ''), '-').$suffix;
    }
}
