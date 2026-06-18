<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->validateCsrfTokens(except: ['api/*', '__matterpipe/*']);
        $middleware->redirectGuestsTo(function (Request $request): string {
            $hostingDomain = rtrim(strtolower((string) config('matterpipe.hosting_domain')), '.');
            $host = rtrim(strtolower($request->getHost()), '.');

            if ($hostingDomain !== '' && str_ends_with($host, ".{$hostingDomain}")) {
                return sprintf(
                    '%s://%s/login',
                    config('matterpipe.hosting_scheme') === 'http' ? 'http' : 'https',
                    $hostingDomain,
                );
            }

            return route('login');
        });

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is(
                'api/*',
                'mcp',
                '.well-known/oauth-*',
                'oauth/register',
                'oauth/token',
            ),
        );
    })->create();
