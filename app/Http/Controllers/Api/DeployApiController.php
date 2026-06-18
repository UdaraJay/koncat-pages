<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserApiToken;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\DeploymentPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeployApiController extends Controller
{
    use ResolvesUserApiToken;

    public function store(Request $request, Project $project, DeploymentPublisher $publisher): JsonResponse
    {
        $user = $this->userFromBearerToken($request);
        abort_unless($user !== null, 401);

        abort_unless($user->canDeployProject($project), 403);

        $validated = $request->validate([
            'archive' => ['required', 'file', 'mimes:zip', 'max:'.ceil(((int) config('matterpipe.quotas.deployment_bytes')) / 1024)],
        ]);

        $deployment = $publisher->publish($project, $validated['archive'], $user);

        return response()->json([
            'deployment' => [
                'id' => $deployment->id,
                'fileCount' => $deployment->file_count,
                'totalBytes' => $deployment->total_bytes,
                'deployedAt' => $deployment->deployed_at->toISOString(),
            ],
            'project' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'url' => $project->url(),
            ],
        ], 201);
    }
}
