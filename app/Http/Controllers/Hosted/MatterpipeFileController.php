<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hosted\Concerns\ResolvesHostedProject;
use App\Models\ProjectFile;
use App\Services\MatterpipeQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatterpipeFileController extends Controller
{
    use ResolvesHostedProject;

    public function store(Request $request, string $team, string $project, MatterpipeQuota $quota): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $file = $validated['file'];
        $quota->ensureProjectCanStoreFile($hostedProject, $file->getSize());

        $disk = (string) config('matterpipe.storage_disk');
        $record = $hostedProject->files()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => $disk,
            'path' => '',
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $path = $file->storeAs(
            'project-files/'.($hostedProject->workspace_id ?? $hostedProject->owner_id)."/{$hostedProject->id}",
            $record->id.'-'.$file->hashName(),
            $disk,
        );

        $record->update(['path' => $path]);

        return response()->json($this->filePayload($record, $project), 201);
    }

    public function show(Request $request, string $team, string $project, ProjectFile $file): StreamedResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        abort_unless($file->project_id === $hostedProject->id, 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroy(Request $request, string $team, string $project, ProjectFile $file): JsonResponse
    {
        $hostedProject = $this->hostedProject($request, $team, $project);
        abort_unless($file->project_id === $hostedProject->id, 404);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return response()->json(null, 204);
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
