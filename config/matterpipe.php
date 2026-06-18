<?php

return [
    'hosting_domain' => env('MATTERPIPE_HOSTING_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
    'hosting_scheme' => env('MATTERPIPE_HOSTING_SCHEME', env('APP_ENV') === 'local' ? 'http' : 'https'),
    'storage_disk' => env('MATTERPIPE_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),

    'quotas' => [
        'user_projects' => (int) env('MATTERPIPE_USER_PROJECTS_LIMIT', 100),
        'team_projects' => (int) env('MATTERPIPE_TEAM_PROJECTS_LIMIT', 500),
        'team_workspaces' => (int) env('MATTERPIPE_TEAM_WORKSPACES_LIMIT', 20),
        'workspace_projects' => (int) env('MATTERPIPE_WORKSPACE_PROJECTS_LIMIT', 50),
        'deployment_bytes' => (int) env('MATTERPIPE_DEPLOYMENT_BYTES_LIMIT', 25 * 1024 * 1024),
        'deployment_files' => (int) env('MATTERPIPE_DEPLOYMENT_FILES_LIMIT', 500),
        'project_documents' => (int) env('MATTERPIPE_PROJECT_DOCUMENTS_LIMIT', 10000),
        'project_file_bytes' => (int) env('MATTERPIPE_PROJECT_FILE_BYTES_LIMIT', 1024 * 1024 * 1024),
    ],
];
