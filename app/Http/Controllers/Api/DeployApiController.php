<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserApiToken;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\DeploymentPublisher;
use App\Services\MatterpipeLimitResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeployApiController extends Controller
{
    use ResolvesUserApiToken;

    public function store(Request $request, Project $project, DeploymentPublisher $publisher, MatterpipeLimitResolver $limits): JsonResponse
    {
        $user = $this->userFromBearerToken($request);
        abort_unless($user !== null, 401);

        abort_unless($user->canDeployProject($project), 403);

        $archiveRules = ['required', 'file', 'mimes:zip'];
        $maxArchiveBytes = $limits->deploymentBytes($project);

        if ($maxArchiveBytes > 0) {
            $archiveRules[] = 'max:'.ceil($maxArchiveBytes / 1024);
        }

        $validated = $request->validate([
            'archive' => $archiveRules,
        ], [
            'archive.max' => 'This deployment is too large.',
        ]);

        $deployment = $publisher->publish($project, $validated['archive'], $user);

        return response()->json([
            'deployment' => [
                'id' => $deployment->id,
                'fileCount' => $deployment->file_count,
                'totalBytes' => $deployment->total_bytes,
                'deployedAt' => $deployment->deployed_at->toISOString(),
                'securityScan' => $deployment->securityScanSummary(),
            ],
            'project' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'url' => $project->url(),
            ],
        ], 201);
    }
}
