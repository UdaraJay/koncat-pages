<?php

namespace App\Services\DeploymentSecurity\Detectors;

use App\Services\DeploymentSecurity\DeploymentScanFile;
use App\Services\DeploymentSecurity\DeploymentScanFinding;

class JavaScriptDetector implements DeploymentSecurityDetector
{
    use DetectsPatternLines;

    /**
     * @return array<int, DeploymentScanFinding>
     */
    public function detect(DeploymentScanFile $file): array
    {
        $findings = [];

        $rules = [
            ['no-eval', 'high', 'Dynamic JavaScript execution is not allowed.', '/\beval\s*\(/i'],
            ['no-new-function', 'high', 'Dynamic JavaScript function construction is not allowed.', '/\bnew\s+Function\s*\(/i'],
            ['no-string-timers', 'high', 'String-based timers execute dynamic code and are not allowed.', '/\bset(?:Timeout|Interval)\s*\(\s*([\'"])/i'],
            ['no-document-cookie', 'high', 'Uploaded pages cannot access document.cookie.', '/\bdocument\s*\.\s*cookie\b/i'],
            ['no-top-navigation', 'high', 'Uploaded pages cannot navigate parent or top frames.', '/(?:\bwindow\s*\.\s*)?(?:\btop|\bparent)\s*\.\s*location(?:\s*[.=]|\s*\[)/i'],
            ['no-remote-dynamic-import', 'high', 'Remote dynamic JavaScript imports are not allowed.', '/\bimport\s*\(\s*([\'"])(?:https?:)?\/\//i'],
            ['no-import-scripts', 'high', 'Worker importScripts is not allowed in uploaded pages.', '/\bimportScripts\s*\(/i'],
            ['network-fetch', 'medium', 'Uploaded page uses fetch; external network access should be reviewed.', '/\bfetch\s*\(/i'],
            ['network-xml-http-request', 'medium', 'Uploaded page uses XMLHttpRequest; external network access should be reviewed.', '/\bXMLHttpRequest\b/'],
            ['network-websocket', 'medium', 'Uploaded page uses WebSocket; external network access should be reviewed.', '/\bWebSocket\s*\(/i'],
            ['network-beacon', 'medium', 'Uploaded page uses beacon APIs; external network access should be reviewed.', '/\b(?:navigator\s*\.\s*)?sendBeacon\s*\(/i'],
            ['browser-storage', 'medium', 'Uploaded page uses browser storage; persistence behavior should be reviewed.', '/\b(?:localStorage|sessionStorage)\b/'],
            ['popup-open', 'medium', 'Uploaded page opens popups; user interaction should be reviewed.', '/\bwindow\s*\.\s*open\s*\(/i'],
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
