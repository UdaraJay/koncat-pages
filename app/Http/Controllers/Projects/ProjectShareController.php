<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectSharePermission;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Services\ProjectShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectShareController extends Controller
{
    public function store(Request $request, Project $project, ProjectShareService $shares): RedirectResponse
    {
        abort_unless($request->user()->canManageProjectShares($project), 403);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'permission' => ['required', 'string', Rule::enum(ProjectSharePermission::class)],
        ]);

        $shares->share(
            project: $project,
            sharer: $request->user(),
            email: $validated['email'],
            permission: ProjectSharePermission::from($validated['permission']),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project shared.')]);

        return back();
    }

    public function update(Request $request, Project $project, ProjectShare $share): RedirectResponse
    {
        abort_unless($share->project_id === $project->id, 404);
        abort_unless($request->user()->canManageProjectShares($project), 403);

        $validated = $request->validate([
            'permission' => ['required', 'string', Rule::enum(ProjectSharePermission::class)],
        ]);

        $share->update([
            'permission' => ProjectSharePermission::from($validated['permission']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project share updated.')]);

        return back();
    }

    public function destroy(Request $request, Project $project, ProjectShare $share): RedirectResponse
    {
        abort_unless($share->project_id === $project->id, 404);
        abort_unless($request->user()->canManageProjectShares($project), 403);

        $share->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project share removed.')]);

        return back();
    }
}
