<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\HostedProjectUrl;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('fetch-project')]
#[Title('Fetch Project')]
#[Description('Fetch a hosted project by URL and return its current deployment files for inspection or updates.')]
#[IsReadOnly]
#[IsIdempotent]
class FetchProjectTool extends Tool
{
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
            throw new AuthenticationException('A valid bearer token is required to fetch a project.');
        }

        $validated = $this->validate($request->all());
        [$team, $slug] = HostedProjectUrl::parse($validated['url']);

        $project = Project::query()
            ->with(['owner', 'workspace.team', 'hostingTeam', 'currentDeployment'])
            ->whereHas('hostingTeam', fn ($query) => $query->where('subdomain', $team))
            ->where('slug', $slug)
            ->first();

        if (! $project instanceof Project) {
            throw ValidationException::withMessages([
                'url' => 'No hosted project could be found for the provided URL.',
            ]);
        }

        if (! $user->canAccessHostedProject($project)) {
            throw new AuthorizationException('You do not have access to this project.');
        }

        $deployment = $project->currentDeployment;

        if (! $deployment instanceof Deployment) {
            throw ValidationException::withMessages([
                'url' => 'This project does not have a current deployment.',
            ]);
        }

        return Response::structured([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'url' => $project->url(),
            ],
            'deployment' => [
                'id' => $deployment->id,
                'fileCount' => $deployment->file_count,
                'totalBytes' => $deployment->total_bytes,
                'deployedAt' => $deployment->deployed_at->toISOString(),
            ],
            'files' => $this->deploymentFiles($deployment),
        ]);
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
                ->description('Hosted project URL. Missing schemes are accepted, such as team.example.com/project.')
                ->max(2048)
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
                'name' => $schema->string()->required(),
                'slug' => $schema->string()->required(),
                'description' => $schema->string(),
                'url' => $schema->string()->required(),
            ])->required(),
            'deployment' => $schema->object([
                'id' => $schema->string()->required(),
                'fileCount' => $schema->integer()->required(),
                'totalBytes' => $schema->integer()->required(),
                'deployedAt' => $schema->string()->required(),
            ])->required(),
            'files' => $schema->array()
                ->items($schema->object([
                    'path' => $schema->string()->required(),
                    'size' => $schema->integer()->required(),
                    'encoding' => $schema->string()->required(),
                    'content' => $schema->string(),
                    'base64' => $schema->string(),
                ])->withoutAdditionalProperties())
                ->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{url: string}
     *
     * @throws ValidationException
     */
    protected function validate(array $arguments): array
    {
        /** @var array{url: string} $validated */
        $validated = Validator::make($arguments, [
            'url' => ['required', 'string', 'max:2048'],
        ])->validate();

        return $validated;
    }

    /**
     * @return array<int, array<string, int|string>>
     *
     * @throws ValidationException
     */
    protected function deploymentFiles(Deployment $deployment): array
    {
        $storage = Storage::disk($deployment->disk);
        $files = [];

        foreach ($deployment->manifest ?? [] as $file) {
            if (! is_array($file) || ! isset($file['path'])) {
                continue;
            }

            $path = (string) $file['path'];
            $contents = $storage->get("{$deployment->path}/{$path}");

            if ($contents === null) {
                throw ValidationException::withMessages([
                    'url' => "Unable to read {$path} from the current deployment.",
                ]);
            }

            $payload = [
                'path' => $path,
                'size' => (int) ($file['size'] ?? strlen($contents)),
            ];

            if ($this->isText($contents)) {
                $payload['encoding'] = 'utf-8';
                $payload['content'] = $contents;
            } else {
                $payload['encoding'] = 'base64';
                $payload['base64'] = base64_encode($contents);
            }

            $files[] = $payload;
        }

        if ($files === []) {
            throw ValidationException::withMessages([
                'url' => 'The current deployment does not contain any readable files.',
            ]);
        }

        return $files;
    }

    protected function isText(string $contents): bool
    {
        return ! str_contains($contents, "\0")
            && mb_check_encoding($contents, 'UTF-8');
    }
}
