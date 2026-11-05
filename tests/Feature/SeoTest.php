<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Settings\SeoSettings;
use Database\Seeders\InstitutionalPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    private function media(string $ext = 'jpg'): Media
    {
        return Media::create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'capa',
            'path' => "media/capa.{$ext}",
            'ext' => $ext,
            'type' => $ext === 'svg' ? 'image/svg+xml' : 'image/jpeg',
            'size' => 2048,
            'width' => 1600,
            'height' => 900,
        ]);
    }

    public function test_head_has_canonical_open_graph_and_twitter_tags(): void
    {
        $this->get('/en/contact')
            ->assertSee('<link rel="canonical" href="'.url('/en/contact').'">', false)
            ->assertSee('<meta property="og:url" content="'.url('/en/contact').'">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta property="og:locale" content="en_GB">', false)
            ->assertSee('<meta property="og:locale:alternate" content="pt_PT">', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }

    public function test_paginated_listings_have_their_own_title_and_canonical(): void
    {
        $this->get('/noticias?page=2&utm_source=x')
            ->assertSee(' | Página 2</title>', false)
            ->assertSee('<link rel="canonical" href="'.url('/noticias').'?page=2">', false);

        $this->get('/noticias?page=1')->assertSee('<link rel="canonical" href="'.url('/noticias').'">', false);
    }

    public function test_search_results_are_not_indexed(): void
    {
        $this->get('/noticias?q=software')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_articles_share_their_cover_as_jpg_and_publication_date(): void
    {
        $post = Post::factory()->create(['cover_image_id' => $this->media()->id, 'published_at' => '2026-09-01 10:00:00']);

        $this->get('/noticias/'.$post->slug)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="article:published_time" content="2026-09-01T10:00:00', false)
            ->assertSeeInOrder(['og:image', 'media/capa.jpg', 'fm=jpg', 'w=1200'], false);
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee("User-agent: *\nDisallow: /", false)->assertDontSee('Sitemap:');

        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertDontSee("Disallow: /\n", false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
    }

    public function test_every_page_has_organization_data(): void
    {
        [$site] = $this->jsonLd($this->get('/')->getContent());
        $types = array_column($site['@graph'], '@type');

        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);

        $organization = $site['@graph'][0];
        $this->assertSame('Beira', $organization['address']['addressLocality']);
        $this->assertSame('MZ', $organization['address']['addressCountry']);
        $this->assertSame('2017', $organization['foundingDate']);
        $this->assertSame(url('/').'/#organization', $organization['@id']);
    }

    public function test_inner_pages_have_breadcrumbs_and_articles_have_blog_posting(): void
    {
        $post = Post::factory()->create(['title' => ['pt' => 'Artigo </script> de teste']]);

        $blocks = $this->jsonLd($this->get('/noticias/'.$post->slug)->getContent());
        $types = array_map(fn (array $block) => $block['@type'] ?? null, $blocks);

        $this->assertContains('NewsArticle', $types);
        $this->assertContains('BreadcrumbList', $types);

        $article = $blocks[array_search('NewsArticle', $types)];
        $this->assertSame('Artigo </script> de teste', $article['headline']);
        $this->assertSame(url('/noticias/'.$post->slug), $article['mainEntityOfPage']);

        $crumbs = $blocks[array_search('BreadcrumbList', $types)]['itemListElement'];
        $this->assertSame(url('/noticias/'.$post->slug), end($crumbs)['item']);
        $this->assertSame(1, $crumbs[0]['position']);
    }

    public function test_ga4_id_is_only_exposed_when_configured_and_never_loaded_server_side(): void
    {
        $this->get('/')->assertDontSee('name="ga4-id"', false);

        $settings = app(SeoSettings::class);
        $settings->ga4_measurement_id = 'G-ABC123';
        $settings->save();

        // O script do Google só é injectado no browser depois do consentimento.
        $this->get('/')
            ->assertSee('<meta name="ga4-id" content="G-ABC123">', false)
            ->assertDontSee('googletagmanager.com', false);
    }

    public function test_successful_forms_mark_the_analytics_event(): void
    {
        $this->withSession(['contact_sent' => true])->get('/contactos')
            ->assertSee('data-analytics-event="generate_lead" data-analytics-form="contacto"', false);

        $this->flushSession();
        $this->get('/contactos')->assertDontSee('data-analytics-event', false);
    }

    public function test_svg_images_fall_back_to_the_default_share_image(): void
    {
        $post = Post::factory()->create(['cover_image_id' => $this->media('svg')->id]);
        $this->get('/noticias/'.$post->slug)->assertSee('content="'.asset('images/og-default.jpg').'"', false);

        $settings = app(SeoSettings::class);
        $settings->og_image_id = $this->media()->id;
        $settings->save();

        $this->get('/noticias/'.$post->slug)->assertSeeInOrder(['og:image', 'media/capa.jpg'], false);
    }
}
