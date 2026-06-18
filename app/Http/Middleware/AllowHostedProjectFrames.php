<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowHostedProjectFrames
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Frame-Options');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        $scheme = config('matterpipe.hosting_scheme') === 'http' ? 'http' : 'https';
        $domain = trim((string) config('matterpipe.hosting_domain'));
        $domain = rtrim(preg_replace('#^[a-z][a-z0-9+\-.]*://#i', '', $domain) ?? $domain, '/.');

        if ($domain === '') {
            return "frame-ancestors 'self'";
        }

        return sprintf(
            "frame-ancestors 'self' %s://%s",
            $scheme,
            $domain,
        );
    }
}
