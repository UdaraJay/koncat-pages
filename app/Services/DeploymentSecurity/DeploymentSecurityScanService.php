<?php

namespace App\Services\DeploymentSecurity;

use App\Models\Deployment;
use App\Models\DeploymentSecurityScan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use LogicException;

class DeploymentSecurityScanService
{
    public function __construct(protected BuiltinDeploymentSecurityScanner $builtinScanner)
    {
        //
    }

    /**
     * @param  array<int, DeploymentScanFile>  $files
     *
     * @throws ValidationException
     */
    public function scanOrFail(Project $project, array $files, ?User $user): ?DeploymentSecurityScan
    {
        if (! $this->enabled()) {
            return null;
        }

        if (! $user instanceof User) {
            throw new LogicException('Deployment security scans require an authenticated user.');
        }

        $startedAt = now();
        $result = $this->scanner()->scan($project, $files);
        $blockedFindings = $result->blockedFindings($this->blockSeverities());

        $scan = DeploymentSecurityScan::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => $blockedFindings === [] ? 'passed' : 'blocked',
            'highest_severity' => $result->highestSeverity(),
            'risk_score' => $result->riskScore(),
            'scanner' => $this->scannerName(),
            'scanner_version' => $this->scannerVersion(),
            'findings' => $result->findingsArray(),
            'metadata' => [
                ...$result->metadata,
                'block_severities' => $this->blockSeverities(),
            ],
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);

        if ($blockedFindings !== []) {
            throw ValidationException::withMessages([
                'security' => $this->blockedMessage($blockedFindings),
            ]);
        }

        return $scan;
    }

    public function attachDeployment(?DeploymentSecurityScan $scan, Deployment $deployment): void
    {
        if (! $scan instanceof DeploymentSecurityScan) {
            return;
        }

        $scan->update(['deployment_id' => $deployment->id]);
    }

    protected function enabled(): bool
    {
        return (bool) config('matterpipe.security_scanning.enabled', true);
    }

    protected function scanner(): DeploymentSecurityScanner
    {
        return $this->builtinScanner;
    }

    protected function scannerName(): string
    {
        return (string) config('matterpipe.security_scanning.scanner', 'builtin');
    }

    protected function scannerVersion(): string
    {
        return (string) config('matterpipe.security_scanning.scanner_version', '1');
    }

    /**
     * @return array<int, string>
     */
    protected function blockSeverities(): array
    {
        $severities = config('matterpipe.security_scanning.block_severities', ['critical', 'high']);

        if (! is_array($severities)) {
            return ['critical', 'high'];
        }

        return array_values(array_map('strtolower', $severities));
    }

    /**
     * @param  array<int, DeploymentScanFinding>  $findings
     */
    protected function blockedMessage(array $findings): string
    {
        $summaries = array_slice(array_map(function (DeploymentScanFinding $finding): string {
            $location = $finding->path.($finding->line ? ':'.$finding->line : '');

            return "{$finding->id} at {$location}";
        }, $findings), 0, 3);

        $suffix = count($findings) > 3 ? ' and '.(count($findings) - 3).' more' : '';

        return 'Deployment blocked by security scan: '.implode('; ', $summaries).$suffix.'.';
    }
}
