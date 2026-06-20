<?php

namespace App\Services\DeploymentSecurity;

class DeploymentScanFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $contents,
        public readonly int $size,
    ) {
        //
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function looksLikeHtml(): bool
    {
        return in_array($this->extension(), ['html', 'htm', 'svg'], true)
            || preg_match('/<(?:!doctype|html|head|body|script|style|svg)\b/i', $this->contents) === 1;
    }
}
