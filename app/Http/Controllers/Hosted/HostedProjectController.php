<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\MatterpipeRuntimeTokens;
use App\Services\ProjectAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HostedProjectController extends Controller
{
    public function __invoke(Request $request, ProjectAnalytics $analytics, MatterpipeRuntimeTokens $tokens, string $team, string $project, ?string $path = null): View
    {
        $hostedProject = $this->resolveProject($request, $team, $project);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $path = trim($path ?: '', '/');
        abort_if($path === '__matterpipe' || str_starts_with($path, '__matterpipe/'), 404);

        $renderPath = $path !== '' ? $path : 'index.html';
        $renderUrl = $tokens->renderUrl($hostedProject, $user, $renderPath);

        $query = collect($request->query())
            ->except(MatterpipeRuntimeTokens::RENDER_QUERY)
            ->all();

        if ($query !== []) {
            $separator = str_contains($renderUrl, '?') ? '&' : '?';
            $renderUrl .= $separator.http_build_query($query);
        }

        $mainAppUrl = rtrim(sprintf(
            '%s://%s',
            config('matterpipe.hosting_scheme'),
            config('matterpipe.hosting_domain'),
        ), '/');
        $analytics->recordProjectView($hostedProject, $user, $path === '' ? '/' : '/'.$path);

        return view('hosted.frame', [
            'dashboardUrl' => "{$mainAppUrl}/home",
            'homeUrl' => "{$mainAppUrl}/",
            'project' => $hostedProject,
            'renderUrl' => $renderUrl,
            'renderOrigin' => sprintf('%s://%s.%s', config('matterpipe.render_scheme'), $team, config('matterpipe.render_domain')),
            'runtimeToken' => $tokens->makeRuntimeToken($hostedProject, $user),
            'user' => $user,
            'userAvatar' => $user?->getAttribute('avatar'),
            'userInitials' => $this->initials($user?->name ?? $user?->email ?? ''),
        ]);
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
