<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HostedProjectController extends Controller
{
    public function __invoke(Request $request, string $team, string $project, ?string $path = null): View
    {
        $hostedProject = $this->resolveProject($request, $team, $project);

        $path = trim($path ?: '', '/');
        abort_if($path === '__matterpipe' || str_starts_with($path, '__matterpipe/'), 404);

        $renderUrl = "/{$project}/__matterpipe/render";

        if ($path !== '') {
            $renderUrl .= '/'.collect(explode('/', $path))
                ->map(fn (string $segment) => rawurlencode($segment))
                ->implode('/');
        } else {
            $renderUrl .= '/index.html';
        }

        if ($request->getQueryString()) {
            $renderUrl .= '?'.$request->getQueryString();
        }

        $mainAppUrl = rtrim(sprintf(
            '%s://%s',
            config('matterpipe.hosting_scheme'),
            config('matterpipe.hosting_domain'),
        ), '/');
        $user = $request->user();

        return view('hosted.frame', [
            'dashboardUrl' => "{$mainAppUrl}/home",
            'homeUrl' => "{$mainAppUrl}/",
            'project' => $hostedProject,
            'renderUrl' => $renderUrl,
            'user' => $user,
            'userAvatar' => $user?->getAttribute('avatar'),
            'userInitials' => $this->initials($user?->name ?? $user?->email ?? ''),
        ]);
    }

    public function render(Request $request, string $team, string $project, ?string $path = null): StreamedResponse
    {
        return $this->streamDeploymentAsset(
            $this->resolveProject($request, $team, $project),
            $path,
        );
    }

    protected function resolveProject(Request $request, string $team, string $project): Project
    {
        $hostedProject = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $project)
            ->firstOrFail();

        abort_unless($request->user()?->canAccessHostedProject($hostedProject), 403);

        return $hostedProject;
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

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));

        if ($parts === []) {
            return '?';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
