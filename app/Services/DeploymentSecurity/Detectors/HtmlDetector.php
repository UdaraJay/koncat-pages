<?php

namespace App\Services\DeploymentSecurity\Detectors;

use App\Services\DeploymentSecurity\DeploymentScanFile;
use App\Services\DeploymentSecurity\DeploymentScanFinding;

class HtmlDetector implements DeploymentSecurityDetector
{
    use DetectsPatternLines;

    public function __construct(
        protected JavaScriptDetector $javaScriptDetector,
        protected CssDetector $cssDetector,
    ) {
        //
    }

    /**
     * @return array<int, DeploymentScanFinding>
     */
    public function detect(DeploymentScanFile $file): array
    {
        $findings = [];

        $rules = [
            ['html-event-handler', 'high', 'Inline HTML event handlers are not allowed.', '/\son[a-z]+\s*=/i'],
            ['html-javascript-url', 'high', 'HTML javascript: URLs are not allowed.', '/\b(?:href|src|action|formaction|xlink:href)\s*=\s*([\'"]?)\s*javascript\s*:/i'],
            ['html-remote-script', 'high', 'Remote executable scripts are not allowed.', '/<script\b(?=[^>]*\bsrc\s*=\s*([\'"]?)\s*(?:https?:)?\/\/)[^>]*>/i'],
            ['html-object-embed', 'high', 'Object and embed tags are not allowed in uploaded pages.', '/<\s*(?:object|embed)\b/i'],
            ['html-iframe', 'high', 'Iframes in uploaded pages require review and are not allowed in V1.', '/<\s*iframe\b/i'],
            ['html-form', 'medium', 'Uploaded page contains a form; submission behavior should be reviewed.', '/<\s*form\b/i'],
            ['html-popup-target', 'medium', 'Uploaded page opens links in a new window or tab.', '/\btarget\s*=\s*([\'"]?)_blank\1/i'],
            ['html-external-asset', 'medium', 'Uploaded page references an external asset URL.', '/<\s*(?:img|link|source|video|audio|track)\b[^>]*(?:src|href|poster)\s*=\s*([\'"]?)\s*(?:https?:)?\/\//i'],
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

        return [
            ...$findings,
            ...$this->inlineScriptFindings($file),
            ...$this->inlineStyleFindings($file),
        ];
    }

    /**
     * @return array<int, DeploymentScanFinding>
     */
    protected function inlineScriptFindings(DeploymentScanFile $file): array
    {
        preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $file->contents, $matches, PREG_OFFSET_CAPTURE);

        $findings = [];

        foreach ($matches[0] as $index => $match) {
            $attributes = (string) ($matches[1][$index][0] ?? '');

            if (preg_match('/\bsrc\s*=/i', $attributes) === 1 || ! $this->scriptTypeIsJavaScript($attributes)) {
                continue;
            }

            $contents = (string) ($matches[2][$index][0] ?? '');
            $line = $this->lineNumber($file->contents, (int) $match[1]);
            $inlineFile = new DeploymentScanFile("{$file->path}#inline-script:{$line}", $contents, strlen($contents));

            $findings = [
                ...$findings,
                ...$this->javaScriptDetector->detect($inlineFile),
            ];
        }

        return $findings;
    }

    /**
     * @return array<int, DeploymentScanFinding>
     */
    protected function inlineStyleFindings(DeploymentScanFile $file): array
    {
        $findings = [];

        preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $file->contents, $styleBlocks, PREG_OFFSET_CAPTURE);

        foreach ($styleBlocks[0] as $index => $match) {
            $contents = (string) ($styleBlocks[1][$index][0] ?? '');
            $line = $this->lineNumber($file->contents, (int) $match[1]);
            $inlineFile = new DeploymentScanFile("{$file->path}#inline-style:{$line}", $contents, strlen($contents));
            $findings = [...$findings, ...$this->cssDetector->detect($inlineFile)];
        }

        preg_match_all('/\bstyle\s*=\s*([\'"])(.*?)\1/is', $file->contents, $styleAttributes, PREG_OFFSET_CAPTURE);

        foreach ($styleAttributes[0] as $index => $match) {
            $contents = (string) ($styleAttributes[2][$index][0] ?? '');
            $line = $this->lineNumber($file->contents, (int) $match[1]);
            $inlineFile = new DeploymentScanFile("{$file->path}#style-attribute:{$line}", $contents, strlen($contents));
            $findings = [...$findings, ...$this->cssDetector->detect($inlineFile)];
        }

        return $findings;
    }

    protected function scriptTypeIsJavaScript(string $attributes): bool
    {
        if (preg_match('/\btype\s*=\s*([\'"]?)([^\'"\s>]+)/i', $attributes, $match) !== 1) {
            return true;
        }

        $type = strtolower($match[2]);

        return in_array($type, [
            'module',
            'text/javascript',
            'application/javascript',
            'application/ecmascript',
            'text/ecmascript',
        ], true);
    }
}
