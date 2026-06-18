<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }} - Koncat</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root {
            color-scheme: light;
            --frame-bg: #f7f7f4;
            --frame-border: #deded8;
            --frame-foreground: #181816;
            --frame-muted: #686861;
            --frame-surface: #ffffff;
            --frame-accent: #2f6f98;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
        }

        body {
            display: grid;
            grid-template-rows: 40px minmax(0, 1fr);
            overflow: hidden;
            margin: 0;
            background: var(--frame-bg);
            color: var(--frame-foreground);
            font-family:
                Inter, ui-sans-serif, system-ui, -apple-system,
                BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .koncat-frame-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-width: 0;
            padding: 0 12px;
        }

        .koncat-frame-brand,
        .koncat-frame-user-link {
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .koncat-frame-brand {
            gap: 8px;
            color: var(--frame-muted);
            font-weight: 450;
            text-decoration: none;
        }

        .koncat-frame-mark {
            display: grid;
            height: 28px;
            flex: 0 0 auto;
            place-items: center;
            color: var(--frame-muted);
        }

        .koncat-frame-mark svg {
            height: 17px;
        }

        .koncat-frame-wordmark {
            line-height: 1;
        }

        .koncat-frame-project {
            min-width: 0;
            overflow: hidden;
            color: var(--frame-muted);
            font-size: 13px;
            text-align: center;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .koncat-frame-user-link {
            gap: 8px;
            justify-content: flex-end;
            color: var(--frame-foreground);
            font-size: 13px;
            text-decoration: none;
            white-space: nowrap;
        }

        .koncat-frame-user-link:hover .koncat-frame-user-name {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .koncat-frame-avatar {
            display: grid;
            width: 24px;
            height: 24px;
            flex: 0 0 auto;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--frame-border);
            border-radius: 999px;
            background: var(--frame-surface);
            color: var(--frame-muted);
            font-size: 10px;
            font-weight: 650;
            line-height: 1;
        }

        .koncat-frame-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .koncat-frame-user-name {
            min-width: 0;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .koncat-frame-app {
            width: 100%;
            height: 100%;
            border: 6px solid var(--frame-bg);
            border-bottom-width: 0;
            border-top-width: 0;
            background: white;
            border-radius: 4px;
            overflow: hidden;

        }

        .koncat-frame-footer {
            padding: 5px 12px;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--frame-muted);
        }

        @media (max-width: 640px) {
            body {
                grid-template-rows: 36px minmax(0, 1fr);
            }

            .koncat-frame-bar {
                gap: 10px;
                padding: 0 10px;
            }

            .koncat-frame-wordmark {
                display: none;
            }

            .koncat-frame-project {
                text-align: left;
            }

            .koncat-frame-user-link {
                max-width: 38vw;
            }

            .koncat-frame-user-name {
                max-width: 100%;
            }


        }
    </style>
</head>

<body>
    <header class="koncat-frame-bar">

        <div class="koncat-frame-project" title="{{ $project->name }}">
            {{ $project->name }}
        </div>
        <a class="koncat-frame-user-link" href="{{ $dashboardUrl }}" title="{{ $user?->email }}" target="_top">
            <span class="koncat-frame-user-name">{{ $user?->name ?? $user?->email }}</span>
            <span class="koncat-frame-avatar" aria-hidden="true">
                @if ($userAvatar)
                    <img src="{{ $userAvatar }}" alt="">
                @else
                    {{ $userInitials }}
                @endif
            </span>
        </a>
    </header>
    <iframe class="koncat-frame-app" src="{{ $renderUrl }}" title="{{ $project->name }}"
        referrerpolicy="same-origin"></iframe>
    <footer class="koncat-frame-footer">
        <div>
            <a class="koncat-frame-brand" href="{{ $homeUrl }}" aria-label="Koncat home" target="_top">
                <span class="koncat-frame-mark" aria-hidden="true">
                    <svg viewBox="0 0 335 170" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M250.238 0C259.103 0 263.536 0 267.27 0.373C302.89 3.931 331.064 32.065 334.627 67.636C335 71.364 335 75.791 335 84.644C335 93.498 335 97.924 334.627 101.652C331.064 137.223 302.89 165.358 267.27 168.915C263.536 169.288 259.103 169.288 250.238 169.288H84.763C75.897 169.288 71.464 169.288 67.731 168.915C32.11 165.358 3.936 137.223 0.373 101.652C0 97.924 0 93.498 0 84.644C0 75.791 0 71.364 0.373 67.636C3.936 32.065 32.11 3.931 67.731 0.373C71.464 0 75.897 0 84.763 0H250.238ZM84.763 56.622C69.265 56.622 56.701 69.168 56.701 84.644C56.701 100.12 69.265 112.666 84.763 112.666H250.238C265.735 112.666 278.299 100.12 278.299 84.644C278.299 69.168 265.735 56.622 250.238 56.622H84.763Z"
                            fill="currentColor" />
                    </svg>
                </span>
                <span class="koncat-frame-wordmark">Published with Koncat</span>
            </a>
        </div>

        <div>
            Created on {{ $project->created_at->toFormattedDateString() }}.
            Last updated {{ $project->updated_at->diffForHumans() }}.
        </div>


    </footer>
</body>

</html>
