<?php

namespace Tests\Feature;

use App\Support\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_restrict_browser_features(): void
    {
        $this->get('/')->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_structured_data_describes_the_association(): void
    {
        $json = json_encode(StructuredData::site(), JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('Agência Privada de Emprego', $json);
        $this->assertStringContainsString('"alternateName":"APE"', $json);
        $this->assertStringContainsString('Sofala', $json);
        $this->assertStringNotContainsString('Maputo', $json);
        $this->assertStringNotContainsString('ProfessionalService', $json);
    }

    public function test_sitemap_lists_real_pages_and_not_the_removed_gallery(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString(url('/vagas'), $sitemap);
        $this->assertStringContainsString(url('/candidatura'), $sitemap);
        $this->assertStringContainsString(url('/empresas'), $sitemap);
        $this->assertStringNotContainsString('galeria', $sitemap);
        $this->get('/galeria')->assertNotFound();
    }

    public function test_robots_blocks_everything_outside_production_and_only_the_panel_in_production(): void
    {
        $this->get('/robots.txt')->assertSee('Disallow: /');

        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }
}
