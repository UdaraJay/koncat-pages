<?php

namespace App\Services\DeploymentSecurity;

class DeploymentScanResult
{
    private const SEVERITY_RANKS = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4,
    ];

    private const RISK_WEIGHTS = [
        'low' => 10,
        'medium' => 25,
        'high' => 60,
        'critical' => 100,
    ];

    /**
     * @param  array<int, DeploymentScanFinding>  $findings
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly array $findings = [],
        public readonly array $metadata = [],
    ) {
        //
    }

    public function highestSeverity(): ?string
    {
        $highest = null;
        $highestRank = 0;

        foreach ($this->findings as $finding) {
            $severity = strtolower($finding->severity);
            $rank = self::SEVERITY_RANKS[$severity] ?? 0;

            if ($rank > $highestRank) {
                $highest = $severity;
                $highestRank = $rank;
            }
        }

        return $highest;
    }

    public function riskScore(): int
    {
        $score = 0;

        foreach ($this->findings as $finding) {
            $score += self::RISK_WEIGHTS[strtolower($finding->severity)] ?? 0;
        }

        return min(100, $score);
    }

    /**
     * @param  array<int, string>  $blockSeverities
     * @return array<int, DeploymentScanFinding>
     */
    public function blockedFindings(array $blockSeverities): array
    {
        $blocked = array_map('strtolower', $blockSeverities);

        return array_values(array_filter(
            $this->findings,
            fn (DeploymentScanFinding $finding) => in_array(strtolower($finding->severity), $blocked, true),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findingsArray(): array
    {
        return array_map(
            fn (DeploymentScanFinding $finding) => $finding->toArray(),
            $this->findings,
        );
    }
}
