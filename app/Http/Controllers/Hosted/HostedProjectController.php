<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HostedProjectController extends Controller
{
    public function __invoke(Request $request, string $team, string $project, ?string $path = null): StreamedResponse
    {
        $hostedProject = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $project)
            ->firstOrFail();

        abort_unless($request->user()?->canAccessHostedProject($hostedProject), 403);
        $deployment = $hostedProject->currentDeployment;
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
}
