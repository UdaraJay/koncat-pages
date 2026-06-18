@php
    $appearance = $appearance ?? 'system';
    $clientName = $client->name ?? 'this application';
    $redirectUri = (string) $request->query('redirect_uri', '');
    $redirectHost = parse_url($redirectUri, PHP_URL_HOST) ?: $redirectUri;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $appearance === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script>
            (function() {
                const appearance = '{{ $appearance }}';

                if (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title>Authorize {{ $clientName }} - {{ config('app.name', 'Matterpipe') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <section class="w-full max-w-md rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col gap-6 p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md border bg-background text-primary shadow-xs">
                            <svg class="size-5" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7.5c0 5.25-3.5 10.1-8 11.5-4.5-1.4-8-6.25-8-11.5V5l8-3 8 3v2.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12 1.75 1.75L15 10" />
                            </svg>
                        </div>

                        <div class="min-w-0 space-y-1">
                            <div class="inline-flex w-fit items-center rounded-full border px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                OAuth access request
                            </div>
                            <h1 class="text-xl font-semibold leading-tight">
                                Authorize {{ $clientName }}
                            </h1>
                            <p class="text-sm leading-5 text-muted-foreground">
                                {{ config('app.name', 'Matterpipe') }} will grant access to your MCP server.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3 rounded-md border bg-background p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-medium">Account</p>
                                <p class="break-all text-sm text-muted-foreground">{{ $user->email }}</p>
                            </div>
                        </div>

                        @if($redirectHost !== '')
                            <div class="border-t pt-3">
                                <p class="text-sm font-medium">Callback</p>
                                <p class="break-all text-sm text-muted-foreground">{{ $redirectHost }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm font-medium">Permissions</p>

                        <ul class="space-y-2">
                            @forelse($scopes as $scope)
                                <li class="flex items-start gap-3 rounded-md border bg-background p-3">
                                    <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                        <svg class="size-3" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                        </svg>
                                    </span>
                                    <span class="text-sm leading-5 text-muted-foreground">{{ $scope->description }}</span>
                                </li>
                            @empty
                                <li class="rounded-md border bg-background p-3 text-sm text-muted-foreground">
                                    Basic account authorization.
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">

                            <button
                                type="submit"
                                class="inline-flex h-9 w-full shrink-0 items-center justify-center gap-2 rounded-md border bg-background px-4 py-2 text-sm font-medium whitespace-nowrap shadow-xs transition-all outline-none hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50"
                            >
                                Cancel
                            </button>
                        </form>

                        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                            @csrf
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">

                            <button
                                type="submit"
                                class="inline-flex h-9 w-full shrink-0 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground transition-all outline-none hover:bg-primary/90 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50"
                            >
                                Authorize
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
