<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hosted\Concerns\ResolvesHostedProject;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\MatterpipeQuota;
use App\Services\MatterpipeRuntimeContext;
use App\Services\MatterpipeRuntimeTokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatterpipeDocumentController extends Controller
{
    use ResolvesHostedProject;

    public function index(Request $request, string $team, string $project, string $collection): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $limit = min(max((int) $request->integer('limit', 50), 1), 100);

        $documents = $hostedProject->documents()
            ->where('collection', $collection)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ProjectDocument $document) => $this->documentPayload($document));

        return response()->json(['data' => $documents]);
    }

    public function store(Request $request, string $team, string $project, string $collection, MatterpipeQuota $quota, MatterpipeRuntimeTokens $tokens): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $runtime = $this->writeRuntime($request, $hostedProject, $tokens);

        $validated = $request->validate([
            'data' => ['required', 'array'],
        ]);

        $quota->ensureProjectCanCreateDocument($hostedProject);

        $document = $hostedProject->documents()->create([
            'collection' => $collection,
            'data' => $validated['data'],
            'created_by' => $runtime->user->id,
            'updated_by' => $runtime->user->id,
        ]);

        return response()->json($this->documentPayload($document), 201);
    }

    public function show(Request $request, string $team, string $project, string $collection, ProjectDocument $document): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $this->authorizeDocument($hostedProject->id, $collection, $document);

        return response()->json($this->documentPayload($document));
    }

    public function update(Request $request, string $team, string $project, string $collection, ProjectDocument $document, MatterpipeRuntimeTokens $tokens): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $runtime = $this->writeRuntime($request, $hostedProject, $tokens);
        $this->authorizeDocument($hostedProject->id, $collection, $document);

        $validated = $request->validate([
            'data' => ['required', 'array'],
        ]);

        $document->update([
            'data' => array_replace_recursive($document->data, $validated['data']),
            'updated_by' => $runtime->user->id,
        ]);

        return response()->json($this->documentPayload($document));
    }

    public function destroy(Request $request, string $team, string $project, string $collection, ProjectDocument $document, MatterpipeRuntimeTokens $tokens): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $this->writeRuntime($request, $hostedProject, $tokens);
        $this->authorizeDocument($hostedProject->id, $collection, $document);

        $document->delete();

        return response()->json(null, 204);
    }

    protected function writeRuntime(Request $request, Project $project, MatterpipeRuntimeTokens $tokens): MatterpipeRuntimeContext
    {
        $context = $tokens->contextFromBearer($request, $project);

        abort_unless($context?->canWrite === true, 403);

        return $context;
    }

    protected function authorizeDocument(string $projectId, string $collection, ProjectDocument $document): void
    {
        abort_unless($document->project_id === $projectId && $document->collection === $collection, 404);
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentPayload(ProjectDocument $document): array
    {
        return [
            'id' => $document->id,
            'data' => $document->data,
            'createdAt' => $document->created_at->toISOString(),
            'updatedAt' => $document->updated_at->toISOString(),
        ];
    }
}
