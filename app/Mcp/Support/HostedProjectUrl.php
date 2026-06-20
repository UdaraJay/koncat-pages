<?php

namespace App\Mcp\Support;

use Illuminate\Validation\ValidationException;

class HostedProjectUrl
{
    /**
     * @return array{0: string, 1: string}
     *
     * @throws ValidationException
     */
    public static function parse(string $url): array
    {
        $url = trim($url);

        if ($url === '') {
            throw ValidationException::withMessages([
                'url' => 'Provide a hosted project URL.',
            ]);
        }

        $normalized = preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url) === 1
            ? $url
            : 'https://'.ltrim($url, '/');

        $parts = parse_url($normalized);

        if (! is_array($parts) || empty($parts['host'])) {
            throw ValidationException::withMessages([
                'url' => 'The hosted project URL could not be parsed.',
            ]);
        }

        $host = rtrim(strtolower($parts['host']), '.');
        $hostingDomain = rtrim(strtolower((string) config('matterpipe.hosting_domain')), '.');
        $renderDomain = rtrim(strtolower((string) config('matterpipe.render_domain')), '.');

        $domain = collect([$hostingDomain, $renderDomain])
            ->filter()
            ->first(fn (string $domain): bool => str_ends_with($host, '.'.$domain));

        if (! is_string($domain)) {
            throw ValidationException::withMessages([
                'url' => "The hosted project URL must use the {$hostingDomain} domain.",
            ]);
        }

        $team = substr($host, 0, -strlen('.'.$domain));
        $path = $parts['path'] ?? '';
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn (string $segment): bool => $segment !== ''));
        $project = rawurldecode($segments[0] ?? '');

        if ($team === '' || $project === '') {
            throw ValidationException::withMessages([
                'url' => 'The hosted project URL must include both a team subdomain and project path.',
            ]);
        }

        return [$team, $project];
    }
}
