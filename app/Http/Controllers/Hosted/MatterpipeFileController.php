<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hosted\Concerns\ResolvesHostedProject;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\MatterpipeLimitResolver;
use App\Services\MatterpipeQuota;
use App\Services\MatterpipeRuntimeContext;
use App\Services\MatterpipeRuntimeTokens;
use App\Services\TeamStoragePaths;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatterpipeFileController extends Controller
{
    use ResolvesHostedProject;

    public function store(Request $request, string $team, string $project, MatterpipeQuota $quota, MatterpipeLimitResolver $limits, MatterpipeRuntimeTokens $tokens, TeamStoragePaths $paths): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $runtime = $this->writeRuntime($request, $hostedProject, $tokens);

        $fileRules = ['required', 'file'];
        $maxUploadBytes = $limits->projectFileUploadBytes($hostedProject);

        if ($maxUploadBytes > 0) {
            $fileRules[] = 'max:'.ceil($maxUploadBytes / 1024);
        }

        $validated = $request->validate([
            'file' => $fileRules,
        ], [
            'file.max' => 'The uploaded file is too large.',
        ]);

        $file = $validated['file'];
        $quota->ensureProjectCanStoreFile($hostedProject, $file->getSize());

        $disk = (string) config('matterpipe.storage_disk');
        $record = $hostedProject->files()->create([
            'uploaded_by' => $runtime->user->id,
            'disk' => $disk,
            'path' => '',
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $path = $file->storeAs($paths->projectFilesDirectory($hostedProject), $paths->projectFileName($record, $file->hashName()), $disk);

        $record->update(['path' => $path]);

        return response()->json($this->filePayload($record, $project), 201);
    }

    public function show(Request $request, string $team, string $project, ProjectFile $file): StreamedResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        abort_unless($file->project_id === $hostedProject->id, 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroy(Request $request, string $team, string $project, ProjectFile $file, MatterpipeRuntimeTokens $tokens): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        $this->writeRuntime($request, $hostedProject, $tokens);
        abort_unless($file->project_id === $hostedProject->id, 404);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return response()->json(null, 204);
    }

    protected function writeRuntime(Request $request, Project $project, MatterpipeRuntimeTokens $tokens): MatterpipeRuntimeContext
    {
        $context = $tokens->contextFromBearer($request, $project);

        abort_unless($context?->canWrite === true, 403);

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    protected function filePayload(ProjectFile $file, string $project): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'mimeType' => $file->mime_type,
            'size' => $file->size,
            'url' => "/{$project}/__matterpipe/files/{$file->id}",
            'createdAt' => $file->created_at->toISOString(),
        ];
    }
}
