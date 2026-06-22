<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectSharePermission;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Services\ProjectShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectShareController extends Controller
{
    public function store(Request $request, Project $project, ProjectShareService $shares): RedirectResponse
    {
        Gate::authorize('create', [ProjectShare::class, $project]);

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
        Gate::authorize('update', $share);

        $validated = $request->validate([
            'permission' => ['required', 'string', Rule::enum(ProjectSharePermission::class)],
        ]);

        $share->update([
            'permission' => ProjectSharePermission::from($validated['permission']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project share updated.')]);

        return back();
    }

    public function destroy(Project $project, ProjectShare $share): RedirectResponse
    {
        abort_unless($share->project_id === $project->id, 404);
        Gate::authorize('delete', $share);

        $share->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project share removed.')]);

        return back();
    }
}
