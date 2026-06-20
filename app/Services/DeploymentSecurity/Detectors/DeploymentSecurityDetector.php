<?php

namespace App\Services\DeploymentSecurity\Detectors;

use App\Services\DeploymentSecurity\DeploymentScanFile;
use App\Services\DeploymentSecurity\DeploymentScanFinding;

interface DeploymentSecurityDetector
{
    /**
     * @return array<int, DeploymentScanFinding>
     */
    public function detect(DeploymentScanFile $file): array;
}
