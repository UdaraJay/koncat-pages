<?php

return [
    'hosting_domain' => env('MATTERPIPE_HOSTING_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
    'hosting_scheme' => env('MATTERPIPE_HOSTING_SCHEME', env('APP_ENV') === 'local' ? 'http' : 'https'),
    'render_domain' => env('MATTERPIPE_RENDER_DOMAIN', 'render.'.(parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')),
    'render_scheme' => env('MATTERPIPE_RENDER_SCHEME', env('APP_ENV') === 'local' ? 'http' : 'https'),
    'storage_disk' => env('MATTERPIPE_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'runtime_token_ttl' => (int) env('MATTERPIPE_RUNTIME_TOKEN_TTL', 10 * 60),
    'render_cookie_ttl' => (int) env('MATTERPIPE_RENDER_COOKIE_TTL', 5 * 60),

    'security_scanning' => [
        'enabled' => env('MATTERPIPE_SECURITY_SCANNING_ENABLED', true),
        'scanner' => env('MATTERPIPE_SECURITY_SCANNER', 'builtin'),
        'block_severities' => ['critical', 'high'],
        'scanner_version' => '1',
    ],

    'quotas' => [
        'user_projects' => (int) env('MATTERPIPE_USER_PROJECTS_LIMIT', 25),
        'team_projects' => (int) env('MATTERPIPE_TEAM_PROJECTS_LIMIT', 100),
        'team_workspaces' => (int) env('MATTERPIPE_TEAM_WORKSPACES_LIMIT', 10),
        'workspace_projects' => (int) env('MATTERPIPE_WORKSPACE_PROJECTS_LIMIT', 25),
        'deployment_files' => (int) env('MATTERPIPE_DEPLOYMENT_FILES_LIMIT', 100),
        'deployment_bytes' => (int) env('MATTERPIPE_DEPLOYMENT_BYTES_LIMIT', 10 * 1024 * 1024),
        'deployment_file_bytes' => (int) env('MATTERPIPE_DEPLOYMENT_FILE_BYTES_LIMIT', 2 * 1024 * 1024),
        'project_files' => (int) env('MATTERPIPE_PROJECT_FILES_LIMIT', 500),
        'project_file_upload_bytes' => (int) env('MATTERPIPE_PROJECT_FILE_UPLOAD_BYTES_LIMIT', 2 * 1024 * 1024),
        'project_file_bytes' => (int) env('MATTERPIPE_PROJECT_FILE_BYTES_LIMIT', 250 * 1024 * 1024),
        'project_documents' => (int) env('MATTERPIPE_PROJECT_DOCUMENTS_LIMIT', 5000),
    ],
];
