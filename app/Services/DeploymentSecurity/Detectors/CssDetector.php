<?php

namespace App\Services\DeploymentSecurity\Detectors;

use App\Services\DeploymentSecurity\DeploymentScanFile;
use App\Services\DeploymentSecurity\DeploymentScanFinding;

class CssDetector implements DeploymentSecurityDetector
{
    use DetectsPatternLines;

    /**
     * @return array<int, DeploymentScanFinding>
     */
    public function detect(DeploymentScanFile $file): array
    {
        $findings = [];

        $rules = [
            ['css-import', 'high', 'CSS @import can load remote styles after scan and is not allowed.', '/@import\b/i'],
            ['css-javascript-url', 'high', 'CSS javascript: URLs are not allowed.', '/url\s*\(\s*([\'"]?)\s*javascript\s*:/i'],
            ['css-external-url', 'medium', 'CSS references an external asset URL.', '/url\s*\(\s*([\'"]?)\s*(?:https?:)?\/\//i'],
        ];

        foreach ($rules as [$id, $severity, $message, $pattern]) {
            preg_match_all($pattern, $file->contents, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as $match) {
                $findings[] = new DeploymentScanFinding(
                    id: $id,
                    severity: $severity,
                    message: $message,
                    path: $file->path,
                    line: $this->lineNumber($file->contents, (int) $match[1]),
                );
            }
        }

        return $findings;
    }
}
