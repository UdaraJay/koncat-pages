<?php

namespace App\Services\DeploymentSecurity;

use App\Models\Project;
use App\Services\DeploymentSecurity\Detectors\CssDetector;
use App\Services\DeploymentSecurity\Detectors\HtmlDetector;
use App\Services\DeploymentSecurity\Detectors\JavaScriptDetector;

class BuiltinDeploymentSecurityScanner implements DeploymentSecurityScanner
{
    protected HtmlDetector $htmlDetector;

    protected JavaScriptDetector $javaScriptDetector;

    protected CssDetector $cssDetector;

    public function __construct()
    {
        $this->javaScriptDetector = new JavaScriptDetector;
        $this->cssDetector = new CssDetector;
        $this->htmlDetector = new HtmlDetector($this->javaScriptDetector, $this->cssDetector);
    }

    /**
     * @param  array<int, DeploymentScanFile>  $files
     */
    public function scan(Project $project, array $files): DeploymentScanResult
    {
        $findings = [];
        $metadata = [
            'files_scanned' => count($files),
            'project_id' => $project->id,
        ];

        foreach ($files as $file) {
            if ($file->looksLikeHtml()) {
                $findings = [...$findings, ...$this->htmlDetector->detect($file)];
            }

            if (in_array($file->extension(), ['js', 'mjs', 'cjs', 'jsx', 'ts', 'tsx'], true)) {
                $findings = [...$findings, ...$this->javaScriptDetector->detect($file)];
            }

            if ($file->extension() === 'css') {
                $findings = [...$findings, ...$this->cssDetector->detect($file)];
            }
        }

        return new DeploymentScanResult($findings, $metadata);
    }
}
