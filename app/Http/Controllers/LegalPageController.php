<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    /**
     * @var array<string, array{description: string, file: string, title: string}>
     */
    private const PAGES = [
        'terms' => [
            'description' => 'The terms that govern your use of Koncat.',
            'file' => 'terms.md',
            'title' => 'Terms of Service',
        ],
        'privacy' => [
            'description' => 'How Koncat handles account, project, and usage data.',
            'file' => 'privacy.md',
            'title' => 'Privacy Policy',
        ],
    ];

    public function terms(): Response
    {
        return $this->show('terms');
    }

    public function privacy(): Response
    {
        return $this->show('privacy');
    }

    private function show(string $page): Response
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        $metadata = self::PAGES[$page];
        $path = storage_path('app/legal/'.$metadata['file']);

        abort_unless(File::isFile($path), 404);

        $markdown = File::get($path);
        $html = Str::markdown($markdown, [
            'allow_unsafe_links' => false,
            'html_input' => 'strip',
        ]);

        return Inertia::render('legal/show', [
            'description' => $metadata['description'],
            'html' => $html,
            'title' => $metadata['title'],
            'updatedAt' => date('F j, Y', File::lastModified($path)),
        ]);
    }
}
