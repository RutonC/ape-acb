<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_in_portuguese_with_brand_assets(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="pt">', false)
            ->assertSee('images/brand/logo-ape.png', false)
            ->assertSee('favicon.png', false);
    }

    public function test_locale_and_timezone_defaults(): void
    {
        $this->assertSame('pt', app()->getLocale());
        $this->assertSame('Africa/Maputo', config('app.timezone'));
        $this->assertSame(['pt', 'en'], array_keys(config('site.locales')));
        $this->assertSame('pt', config('site.default_locale'));
    }

    public function test_brand_files_exist(): void
    {
        foreach (['logo-ape.png'] as $file) {
            $this->assertFileExists(public_path("images/brand/{$file}"));
        }

        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('favicon.png'));
    }
}
