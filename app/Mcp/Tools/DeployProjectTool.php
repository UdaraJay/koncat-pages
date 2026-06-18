<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\DeploymentPublisher;
use App\Services\MatterpipeQuota;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('deploy-project')]
#[Title('Deploy Project')]
#[Description('Create a personal static project from inline files, publish it, and return the hosted URL.')]
class DeployProjectTool extends Tool
{
    public function __construct(
        protected DeploymentPublisher $publisher,
        protected MatterpipeQuota $quota,
    ) {
        //
    }

    /**
     * Handle the tool request.
     *
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('A valid bearer token is required to deploy a project.');
        }

        $validated = $this->validate($request->all(), $user);
        $files = $this->normalizeFiles($validated['files']);

        $result = DB::transaction(function () use ($user, $validated, $files): array {
            $this->quota->ensureUserCanCreateProject($user);
            $hostingTeamId = $user->personalTeam()?->id;
            abort_unless($hostingTeamId !== null, 422);

            $project = $user->projects()->create([
                'hosting_team_id' => $hostingTeamId,
                'created_by' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'slug' => $validated['slug'] ?? null,
            ]);

            $deployment = $this->publisher->publishFiles($project, $files, $user);

            return [
                'project' => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'url' => $project->url(),
                ],
                'deployment' => [
                    'id' => $deployment->id,
                    'fileCount' => $deployment->file_count,
                    'totalBytes' => $deployment->total_bytes,
                    'deployedAt' => $deployment->deployed_at->toISOString(),
                ],
            ];
        });

        return Response::structured($result);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The project name.')
                ->max(255)
                ->required(),
            'slug' => $schema->string()
                ->description('Optional project path scoped to the user personal team subdomain. Must contain only ASCII letters, numbers, dashes, and underscores.')
                ->max(80),
            'description' => $schema->string()
                ->description('Optional project description.')
                ->max(2000),
            'files' => $schema->array()
                ->description('Files to publish. Each file must have a relative path and exactly one of content or base64.')
                ->min(1)
                ->items($schema->object([
                    'path' => $schema->string()
                        ->description('Relative file path, such as index.html or assets/app.js.')
                        ->required(),
                    'content' => $schema->string()
                        ->description('UTF-8 text file contents.'),
                    'base64' => $schema->string()
                        ->description('Base64-encoded file contents for binary files.'),
                ])->withoutAdditionalProperties())
                ->required(),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->object([
                'id' => $schema->string()->required(),
                'slug' => $schema->string()->required(),
                'url' => $schema->string()->required(),
            ])->required(),
            'deployment' => $schema->object([
                'id' => $schema->string()->required(),
                'fileCount' => $schema->integer()->required(),
                'totalBytes' => $schema->integer()->required(),
                'deployedAt' => $schema->string()->required(),
            ])->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{name: string, slug?: string|null, description?: string|null, files: array<int, array{path: string, content?: string|null, base64?: string|null}>}
     *
     * @throws ValidationException
     */
    protected function validate(array $arguments, User $user): array
    {
        $hostingTeamId = $user->personalTeam()?->id;

        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                'alpha_dash:ascii',
                Rule::unique('projects', 'slug')->where('hosting_team_id', $hostingTeamId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'array'],
            'files.*.path' => ['required', 'string'],
            'files.*.content' => ['nullable', 'string'],
            'files.*.base64' => ['nullable', 'string'],
        ], [
            'files.required' => 'Provide at least one file to deploy.',
            'files.*.path.required' => 'Every file must include a relative path.',
        ]);

        $validator->after(function ($validator): void {
            foreach ($validator->getData()['files'] ?? [] as $index => $file) {
                if (! is_array($file)) {
                    continue;
                }

                $hasContent = array_key_exists('content', $file) && $file['content'] !== null;
                $hasBase64 = array_key_exists('base64', $file) && $file['base64'] !== null;

                if ($hasContent === $hasBase64) {
                    $validator->errors()->add(
                        "files.{$index}",
                        'Each file must include exactly one of content or base64.',
                    );
                }

                if ($hasBase64 && base64_decode((string) $file['base64'], true) === false) {
                    $validator->errors()->add(
                        "files.{$index}.base64",
                        'The base64 file content must be valid base64.',
                    );
                }
            }
        });

        /** @var array{name: string, slug?: string|null, description?: string|null, files: array<int, array{path: string, content?: string|null, base64?: string|null}>} $validated */
        $validated = $validator->validate();

        return $validated;
    }

    /**
     * @param  array<int, array{path: string, content?: string|null, base64?: string|null}>  $files
     * @return array<int, array{path: string, contents: string}>
     *
     * @throws ValidationException
     */
    protected function normalizeFiles(array $files): array
    {
        return array_map(function (array $file): array {
            if (array_key_exists('base64', $file) && $file['base64'] !== null) {
                $contents = base64_decode($file['base64'], true);

                if ($contents === false) {
                    throw ValidationException::withMessages([
                        'files' => 'The base64 file content must be valid base64.',
                    ]);
                }
            } else {
                $contents = $file['content'] ?? '';
            }

            return [
                'path' => $file['path'],
                'contents' => $contents,
            ];
        }, $files);
    }
}
