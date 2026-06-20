<?php

namespace App\Services\DeploymentSecurity;

use App\Models\Project;

interface DeploymentSecurityScanner
{
    /**
     * @param  array<int, DeploymentScanFile>  $files
     */
    public function scan(Project $project, array $files): DeploymentScanResult;
}
