<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\HostedProjectUrl;
use App\Models\Project;
use App\Models\User;
use App\Services\DeploymentPublisher;
use App\Services\MatterpipeQuota;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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

#[Name('publish')]
#[Title('Publish')]
#[Description('Create a new hosted static project or update an existing hosted project by URL, publish the provided files, and return the hosted URL.')]
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
     * @throws AuthorizationException
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

        $result = ! empty($validated['url'])
            ? $this->updateProject($user, $validated, $files)
            : $this->createProject($user, $validated, $files);

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
            'url' => $schema->string()
                ->description('Existing hosted or render project URL to update. Missing schemes, ports, query strings, fragments, and asset paths are accepted. Omit this to create a new personal project.')
                ->max(2048),
            'name' => $schema->string()
                ->description('The project name. Required when creating a new project. Optional when updating; when provided, the project name is updated.')
                ->max(255),
            'slug' => $schema->string()
                ->description('Optional project path when creating a new project. Must contain only ASCII letters, numbers, dashes, and underscores. Cannot be used when updating by URL.')
                ->max(80),
            'description' => $schema->string()
                ->description('Optional project description. When updating, providing this value updates the description; null clears it.')
                ->max(2000),
            'files' => $schema->array()
                ->description('Full replacement set of files to publish. Each file must have a relative path and exactly one of content or base64.')
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
            'action' => $schema->string()->required(),
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
                'securityScan' => $schema->object([
                    'status' => $schema->string()->required(),
                    'highestSeverity' => $schema->string(),
                    'riskScore' => $schema->integer()->required(),
                    'findingsCount' => $schema->integer()->required(),
                    'scannedAt' => $schema->string(),
                ]),
            ])->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{url?: string|null, name?: string|null, slug?: string|null, description?: string|null, files: array<int, array{path: string, content?: string|null, base64?: string|null}>}
     *
     * @throws ValidationException
     */
    protected function validate(array $arguments, User $user): array
    {
        $hostingTeamId = $user->personalTeam()?->id;
        $deploymentFileLimit = $this->deploymentLimit('deployment_file_bytes');
        $deploymentFilesLimit = $this->deploymentLimit('deployment_files');
        $filesRules = ['required', 'array', 'min:1'];
        $contentRules = ['nullable', 'string'];

        if ($deploymentFilesLimit > 0) {
            $filesRules[] = 'max:'.$deploymentFilesLimit;
        }

        if ($deploymentFileLimit > 0) {
            $contentRules[] = 'max:'.$deploymentFileLimit;
        }

        $validator = Validator::make($arguments, [
            'url' => ['nullable', 'string', 'max:2048'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                'alpha_dash:ascii',
                Rule::unique('projects', 'slug')->where('hosting_team_id', $hostingTeamId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'files' => $filesRules,
            'files.*' => ['required', 'array'],
            'files.*.path' => ['required', 'string', 'max:512'],
            'files.*.content' => $contentRules,
            'files.*.base64' => ['nullable', 'string'],
        ], [
            'files.required' => 'Provide at least one file to deploy.',
            'files.max' => 'This deployment has too many files.',
            'files.*.path.required' => 'Every file must include a relative path.',
            'files.*.path.max' => 'File paths may not be longer than 512 characters.',
            'files.*.content.max' => 'This deployment contains a file that is too large.',
        ]);

        $validator->after(function ($validator): void {
            $data = $validator->getData();
            $isUpdate = ! empty($data['url']);
            $totalBytes = 0;
            $deploymentBytesLimit = $this->deploymentLimit('deployment_bytes');
            $deploymentFileLimit = $this->deploymentLimit('deployment_file_bytes');

            if (! $isUpdate && empty($data['name'])) {
                $validator->errors()->add('name', 'The project name is required when creating a project.');
            }

            if ($isUpdate && ! empty($data['slug'])) {
                $validator->errors()->add('slug', 'The slug can only be provided when creating a project.');
            }

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

                if ($hasContent) {
                    $contentBytes = strlen((string) $file['content']);

                    if ($deploymentFileLimit > 0 && $contentBytes > $deploymentFileLimit) {
                        $validator->errors()->add(
                            "files.{$index}.content",
                            'This deployment contains a file that is too large.',
                        );
                    }

                    $totalBytes += $contentBytes;
                }

                if ($hasBase64) {
                    $encoded = (string) $file['base64'];
                    $decodedBytes = $this->estimatedBase64DecodedBytes($encoded);

                    if ($decodedBytes === null) {
                        $validator->errors()->add(
                            "files.{$index}.base64",
                            'The base64 file content must be valid base64.',
                        );
                    } else {
                        if ($deploymentFileLimit > 0 && $decodedBytes > $deploymentFileLimit) {
                            $validator->errors()->add(
                                "files.{$index}.base64",
                                'This deployment contains a file that is too large.',
                            );
                        }

                        $totalBytes += $decodedBytes;
                    }
                }

                if ($deploymentBytesLimit > 0 && $totalBytes > $deploymentBytesLimit) {
                    $validator->errors()->add(
                        'files',
                        'This deployment is too large.',
                    );
                }
            }
        });

        /** @var array{url?: string|null, name?: string|null, slug?: string|null, description?: string|null, files: array<int, array{path: string, content?: string|null, base64?: string|null}>} $validated */
        $validated = $validator->validate();

        return $validated;
    }

    /**
     * @param  array{url?: string|null, name?: string|null, slug?: string|null, description?: string|null, files: array<int, array{path: string, content?: string|null, base64?: string|null}>}  $validated
     * @param  array<int, array{path: string, contents: string}>  $files
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function createProject(User $user, array $validated, array $files): array
    {
        $this->quota->ensureUserCanCreateProject($user);
        $hostingTeamId = $user->personalTeam()?->id;
        $name = $validated['name'] ?? null;

        abort_unless($hostingTeamId !== null, 422);

        if (! is_string($name) || $name === '') {
            throw ValidationException::withMessages(['name' => 'The project name is required when creating a project.']);
        }

        $project = $user->projects()->create([
            'hosting_team_id' => $hostingTeamId,
            'created_by' => $user->id,
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'slug' => $validated['slug'] ?? null,
        ]);

        try {
            $deployment = $this->publisher->publishFiles($project, $files, $user);
        } catch (ValidationException $exception) {
            if (! $this->hasSecurityError($exception)) {
                $project->forceDelete();
            }

            throw $exception;
        }

        return [
            'action' => 'created',
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
                'securityScan' => $deployment->securityScanSummary(),
            ],
        ];
    }

    /**
     * @param  array{url?: string|null, name?: string|null, slug?: string|null, description?: string|null, files: array<int, array{path: string, content?: string|null, base64?: string|null}>}  $validated
     * @param  array<int, array{path: string, contents: string}>  $files
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    protected function updateProject(User $user, array $validated, array $files): array
    {
        $url = $validated['url'] ?? null;

        if (! is_string($url) || $url === '') {
            throw ValidationException::withMessages(['url' => 'The hosted project URL is required when updating a project.']);
        }

        [$team, $slug] = HostedProjectUrl::parse($url);

        $project = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $slug)
            ->first();

        if (! $project instanceof Project) {
            throw ValidationException::withMessages([
                'url' => 'No hosted project could be found for the provided URL.',
            ]);
        }

        if (! $user->canDeployProject($project)) {
            throw new AuthorizationException('You do not have permission to deploy this project.');
        }

        $updates = [];

        if (array_key_exists('name', $validated) && $validated['name'] !== null) {
            $updates['name'] = $validated['name'];
        }

        if (array_key_exists('description', $validated)) {
            $updates['description'] = $validated['description'];
        }

        $deployment = $this->publisher->publishFiles($project, $files, $user);

        if ($updates !== []) {
            $project->update($updates);
        }

        return [
            'action' => 'updated',
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
                'securityScan' => $deployment->securityScanSummary(),
            ],
        ];
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

    protected function deploymentLimit(string $key): int
    {
        return (int) config("matterpipe.quotas.{$key}", 0);
    }

    protected function hasSecurityError(ValidationException $exception): bool
    {
        return array_key_exists('security', $exception->errors());
    }

    protected function estimatedBase64DecodedBytes(string $encoded): ?int
    {
        if ($encoded === '') {
            return 0;
        }

        if (strlen($encoded) % 4 !== 0 || preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $encoded) !== 1) {
            return null;
        }

        $padding = substr_count(substr($encoded, -2), '=');

        return intdiv(strlen($encoded), 4) * 3 - $padding;
    }
}
