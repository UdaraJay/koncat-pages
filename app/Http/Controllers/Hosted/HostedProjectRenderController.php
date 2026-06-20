<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\MatterpipeRuntimeTokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HostedProjectRenderController extends Controller
{
    public function __invoke(Request $request, MatterpipeRuntimeTokens $tokens, string $team, string $project, ?string $path = null): RedirectResponse|StreamedResponse
    {
        $hostedProject = $this->resolveProject($team, $project);

        if ($queryToken = $request->query(MatterpipeRuntimeTokens::RENDER_QUERY)) {
            abort_unless(is_string($queryToken) && $tokens->validateRenderToken($queryToken, $hostedProject) !== null, 403);

            return redirect()
                ->to($request->fullUrlWithoutQuery(MatterpipeRuntimeTokens::RENDER_QUERY))
                ->withCookie($this->renderCookie($tokens, $queryToken, $project));
        }

        $cookieToken = $request->cookie(MatterpipeRuntimeTokens::RENDER_COOKIE);
        abort_unless(is_string($cookieToken) && $tokens->validateRenderToken($cookieToken, $hostedProject) !== null, 403);

        return $this->streamDeploymentAsset($hostedProject, $path);
    }

    protected function resolveProject(string $team, string $project): Project
    {
        return Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $project)
            ->firstOrFail();
    }

    protected function streamDeploymentAsset(Project $project, ?string $path = null): StreamedResponse
    {
        $deployment = $project->currentDeployment;
        abort_unless($deployment !== null, 404);

        $path = trim($path ?: 'index.html', '/');

        if ($path === '' || str_ends_with($path, '/')) {
            $path .= 'index.html';
        }

        abort_if($path === '__matterpipe' || str_starts_with($path, '__matterpipe/'), 404);

        $disk = Storage::disk($deployment->disk);
        $assetPath = $deployment->path.'/'.$path;

        if (! $disk->exists($assetPath)) {
            $assetPath = $deployment->path.'/index.html';
        }

        abort_unless($disk->exists($assetPath), 404);

        return $disk->response($assetPath);
    }

    protected function renderCookie(MatterpipeRuntimeTokens $tokens, string $value, string $project): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make(
            MatterpipeRuntimeTokens::RENDER_COOKIE,
            $value,
            $tokens->renderCookieMinutes(),
            '/'.$project,
            null,
            config('matterpipe.render_scheme') === 'https',
            true,
            false,
            'lax',
        );
    }
}
