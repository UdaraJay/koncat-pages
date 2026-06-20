<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class MatterpipeRuntimeTokens
{
    public const RENDER_QUERY = '__matterpipe_render_token';

    public const RENDER_COOKIE = 'matterpipe_render';

    public function makeRuntimeToken(Project $project, User $user): string
    {
        return $this->encrypt([
            'type' => 'runtime',
            'project_id' => $project->id,
            'user_id' => $user->id,
            'can_write' => $user->canWriteProjectContent($project),
            'expires_at' => now()->addSeconds($this->runtimeTokenTtl())->timestamp,
        ]);
    }

    public function makeRenderToken(Project $project, User $user): string
    {
        return $this->encrypt([
            'type' => 'render',
            'project_id' => $project->id,
            'user_id' => $user->id,
            'deployment_id' => $project->current_deployment_id,
            'expires_at' => now()->addSeconds($this->renderCookieTtl())->timestamp,
        ]);
    }

    public function renderUrl(Project $project, User $user, string $path = 'index.html'): string
    {
        $team = $project->hostingTeam;
        abort_unless($team instanceof Team, 404);

        $url = rtrim(sprintf(
            '%s://%s.%s/%s',
            config('matterpipe.render_scheme'),
            $team->subdomain,
            config('matterpipe.render_domain'),
            $project->slug,
        ), '/');

        $path = trim($path, '/');

        if ($path !== '') {
            $url .= '/'.collect(explode('/', $path))
                ->map(fn (string $segment) => rawurlencode($segment))
                ->implode('/');
        }

        return $url.'?'.static::RENDER_QUERY.'='.rawurlencode($this->makeRenderToken($project, $user));
    }

    public function contextFromBearer(Request $request, Project $project): ?MatterpipeRuntimeContext
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        $payload = $this->decrypt($token);

        if (! $payload || ($payload['type'] ?? null) !== 'runtime') {
            return null;
        }

        if (($payload['project_id'] ?? null) !== $project->id || ! $this->isFresh($payload)) {
            return null;
        }

        $user = User::query()->find($payload['user_id'] ?? null);

        if (! $user instanceof User || ! $user->canAccessHostedProject($project)) {
            return null;
        }

        $canWrite = (bool) ($payload['can_write'] ?? false)
            && $user->canWriteProjectContent($project);

        return new MatterpipeRuntimeContext($project, $user, $canWrite);
    }

    public function validateRenderToken(string $token, Project $project): ?User
    {
        $payload = $this->decrypt($token);

        if (! $payload || ($payload['type'] ?? null) !== 'render') {
            return null;
        }

        if (($payload['project_id'] ?? null) !== $project->id || ! $this->isFresh($payload)) {
            return null;
        }

        if (($payload['deployment_id'] ?? null) !== $project->current_deployment_id) {
            return null;
        }

        $user = User::query()->find($payload['user_id'] ?? null);

        if (! $user instanceof User || ! $user->canAccessHostedProject($project)) {
            return null;
        }

        return $user;
    }

    public function renderCookieMinutes(): int
    {
        return max(1, (int) ceil($this->renderCookieTtl() / 60));
    }

    protected function runtimeTokenTtl(): int
    {
        return max(60, (int) config('matterpipe.runtime_token_ttl', 600));
    }

    protected function renderCookieTtl(): int
    {
        return max(60, (int) config('matterpipe.render_cookie_ttl', 300));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function encrypt(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decrypt(string $token): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isFresh(array $payload): bool
    {
        return is_int($payload['expires_at'] ?? null)
            && $payload['expires_at'] >= now()->timestamp;
    }
}
