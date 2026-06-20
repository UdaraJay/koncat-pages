<?php

namespace App\Services\DeploymentSecurity;

class DeploymentScanFinding
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $severity,
        public readonly string $message,
        public readonly string $path,
        public readonly ?int $line = null,
        public readonly array $metadata = [],
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'severity' => $this->severity,
            'message' => $this->message,
            'path' => $this->path,
            'line' => $this->line,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
