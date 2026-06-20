<?php

namespace App\Services\DeploymentSecurity\Detectors;

trait DetectsPatternLines
{
    protected function lineNumber(string $contents, int $offset): int
    {
        return substr_count(substr($contents, 0, max(0, $offset)), "\n") + 1;
    }
}
