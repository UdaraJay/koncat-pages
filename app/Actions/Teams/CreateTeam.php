<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTeam
{
    /**
     * Create a new team and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false, ?string $subdomain = null): Team
    {
        return DB::transaction(function () use ($user, $name, $isPersonal, $subdomain) {
            $team = Team::create([
                'name' => $name,
                'subdomain' => $subdomain,
                'is_personal' => $isPersonal,
            ]);

            $membership = $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $user->switchTeam($team);

            return $team;
        });
    }

    /**
     * Create the user's personal team during signup.
     */
    public function handlePersonal(User $user): Team
    {
        $firstName = $this->firstName($user->name);

        return $this->handle(
            user: $user,
            name: $firstName."'s team",
            isPersonal: true,
            subdomain: $this->generatePersonalSubdomain($firstName),
        );
    }

    protected function firstName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $name));

        return Str::before($name, ' ') ?: 'User';
    }

    protected function generatePersonalSubdomain(string $firstName): string
    {
        $base = Str::slug($firstName) ?: 'team';
        $base = trim(Str::limit($base, 63, ''), '-');

        if (! $this->subdomainExists($base)) {
            return $base;
        }

        do {
            $suffix = '-'.Str::lower(Str::random(6));
            $subdomain = trim(Str::limit($base, 63 - strlen($suffix), ''), '-').$suffix;
        } while ($this->subdomainExists($subdomain));

        return $subdomain;
    }

    protected function subdomainExists(string $subdomain): bool
    {
        return Team::withTrashed()
            ->where('subdomain', $subdomain)
            ->exists();
    }
}
