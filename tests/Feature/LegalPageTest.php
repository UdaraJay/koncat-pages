<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_terms_page_is_rendered_from_markdown(): void
    {
        $this
            ->get(route('legal.terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('legal/show')
                ->where('title', 'Terms of Service')
                ->where('description', 'The terms that govern your use of Koncat.')
                ->where('html', fn (string $html) => str_contains($html, '<h1>Terms of Service</h1>')),
            );
    }

    public function test_privacy_page_is_rendered_from_markdown(): void
    {
        $this
            ->get(route('legal.privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('legal/show')
                ->where('title', 'Privacy Policy')
                ->where('description', 'How Koncat handles account, project, and usage data.')
                ->where('html', fn (string $html) => str_contains($html, '<h1>Privacy Policy</h1>')),
            );
    }

    public function test_tos_redirects_to_terms(): void
    {
        $this
            ->get(route('legal.tos'))
            ->assertRedirect('/terms');
    }
}
