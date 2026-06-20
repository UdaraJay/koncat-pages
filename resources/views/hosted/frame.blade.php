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
            /* gap: 8px; */
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
    <script>
        (() => {
            const runtimeToken = @json($runtimeToken);
            const matterpipeBase = @json('/' . $project->slug . '/__matterpipe');
            const allowed = (method, path) => {
                if (typeof method !== 'string' || typeof path !== 'string' || !path.startsWith('/')) {
                    return false;
                }

                const upperMethod = method.toUpperCase();

                if (upperMethod === 'GET' && path === '/identity') {
                    return true;
                }

                if (path.startsWith('/db/')) {
                    return ['GET', 'POST', 'PATCH', 'DELETE'].includes(upperMethod);
                }

                if (path === '/files') {
                    return upperMethod === 'POST';
                }

                return path.startsWith('/files/') && ['GET', 'DELETE'].includes(upperMethod);
            };

            window.addEventListener('message', async (event) => {
                const appFrame = document.querySelector('.koncat-frame-app');

                if (event.source !== appFrame?.contentWindow || !event.data || event.data.type !== 'matterpipe:request') {
                    return;
                }

                const id = event.data.id;
                const method = String(event.data.method || 'GET').toUpperCase();
                const path = String(event.data.path || '');

                try {
                    if (!allowed(method, path)) {
                        throw Object.assign(new Error('Matterpipe request is not allowed'), { status: 403, body: 'Forbidden' });
                    }

                    const headers = {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${runtimeToken}`,
                    };
                    const init = {
                        method,
                        credentials: 'same-origin',
                        headers,
                    };

                    if (event.data.file) {
                        const form = new FormData();
                        form.append('file', event.data.file);
                        init.body = form;
                    } else if (Object.prototype.hasOwnProperty.call(event.data, 'json')) {
                        headers['Content-Type'] = 'application/json';
                        init.body = JSON.stringify(event.data.json);
                    }

                    const response = await fetch(matterpipeBase + path, init);
                    const body = await response.text();

                    event.source?.postMessage({
                        type: 'matterpipe:response',
                        id,
                        ok: response.ok,
                        status: response.status,
                        body,
                    }, event.origin === 'null' ? '*' : event.origin);
                } catch (error) {
                    event.source?.postMessage({
                        type: 'matterpipe:response',
                        id,
                        ok: false,
                        status: error.status || 500,
                        body: error.body || error.message || 'Matterpipe request failed',
                    }, event.origin === 'null' ? '*' : event.origin);
                }
            });
        })();
    </script>
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
        sandbox="allow-scripts allow-forms allow-downloads allow-modals allow-popups"
        referrerpolicy="no-referrer"></iframe>
    <footer class="koncat-frame-footer">
        <div>
            <a class="koncat-frame-brand" href="{{ $homeUrl }}" aria-label="Koncat home" target="_top">
                <span class="koncat-frame-mark" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 957 897" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M953 732.851C953 821.181 881.245 892.998 792.979 893C782.386 893 771.811 891.909 761.55 889.889L761.538 889.887L500.872 837.713C486.106 834.757 470.901 834.756 456.135 837.71L195.317 889.887C185.2 891.91 174.616 893 164.024 893C75.7559 893 4.00006 821.181 3.99997 732.851L3.99999 164.149C4.00012 75.8181 75.7573 4.00013 164.024 3.99997C174.618 3.99997 185.192 5.09095 195.453 7.1113L195.466 7.11325L456.126 59.2861C470.895 62.2423 486.105 62.2423 500.874 59.2861L761.534 7.11327L761.546 7.11132C771.802 5.09089 782.382 3.99999 792.976 3.99999C881.244 4.00015 953 75.8194 953 164.149L953 732.851ZM428.813 210.693C428.813 178.289 405.947 150.387 374.174 144.02L175.831 104.272L174.379 103.983C170.974 103.419 167.573 103.14 164.019 103.14C130.412 103.14 103.06 130.508 103.06 164.149L103.06 736.919L103.044 736.918C105.144 768.669 131.626 793.858 163.866 793.858C167.834 793.858 171.842 793.433 175.734 792.709L175.734 792.708L374.166 752.968C405.943 746.604 428.813 718.7 428.813 686.292L428.813 210.693ZM853.936 164.147C853.936 130.506 826.584 103.137 792.978 103.137C789.559 103.137 786.002 103.419 782.6 103.984L582.676 144.026C550.9 150.391 528.03 178.295 528.03 210.702L528.03 686.302C528.03 718.706 550.896 746.608 582.669 752.976L780.819 792.684L780.936 792.705C785.004 793.434 789.013 793.858 792.978 793.858C826.584 793.858 853.935 766.49 853.936 732.849L853.936 164.147Z"
                            fill="currentcolor" stroke="currentcolor" stroke-width="8" />
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
