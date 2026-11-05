<?php

namespace Tests\Feature;

use App\Support\Localization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class LocalizedRoutesTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    /**
     * @return array<string, array{string}>
     */
    public static function publicPaths(): array
    {
        return [
            'início' => ['/', '/en'],
            'sobre' => ['/sobre-nos', '/en/about-us'],
            'serviços' => ['/servicos', '/en/services'],
            'serviço' => ['/servicos/recrutamento-e-seleccao', '/en/services/recrutamento-e-seleccao'],
            'vagas' => ['/vagas', '/en/jobs'],
            'candidatura' => ['/candidatura', '/en/apply'],
            'empresas' => ['/empresas', '/en/companies'],
            'parceiros' => ['/parceiros', '/en/partners'],
            'blog' => ['/noticias', '/en/news'],
            'contactos' => ['/contactos', '/en/contact'],
            'privacidade' => ['/politica-de-privacidade', '/en/privacy-policy'],
            'termos' => ['/termos-de-utilizacao', '/en/terms-of-use'],
        ];
    }

    #[DataProvider('publicPaths')]
    public function test_public_pages_render_in_portuguese_and_english(string $path, string $englishPath): void
    {
        $this->get($path)->assertOk()->assertSee('<html lang="pt">', false);
        $this->get($englishPath)->assertOk()->assertSee('<html lang="en">', false);
    }

    public function test_unknown_service_or_product_returns_404(): void
    {
        $this->get('/servicos/nao-existe')->assertNotFound();
        $this->get('/en/products/nao-existe')->assertNotFound();
    }

    public function test_lroute_follows_current_locale(): void
    {
        app()->setLocale('pt');
        $this->assertSame(url('/contactos'), lroute('contact'));

        app()->setLocale('en');
        $this->assertSame(url('/en/contact'), lroute('contact'));
        $this->assertSame(url('/en/services/centro-de-avaliacao'), lroute('services.show', ['slug' => 'centro-de-avaliacao']));
    }

    public function test_language_switcher_points_to_the_same_page(): void
    {
        $this->get('/servicos/centro-de-avaliacao')
            ->assertSee('href="'.url('/en/services/centro-de-avaliacao').'"', false);

        $this->get('/en/services/centro-de-avaliacao')
            ->assertSee('href="'.url('/servicos/centro-de-avaliacao').'"', false);
    }

    public function test_base_route_name_strips_locale_prefix(): void
    {
        $this->assertSame('contact', Localization::baseRouteName('en.contact'));
        $this->assertSame('contact', Localization::baseRouteName('contact'));
        $this->assertNull(Localization::baseRouteName(null));
    }

    public function test_path_translates_fixed_segments_only(): void
    {
        $this->assertSame('/news/category/{slug}', Localization::path('/noticias/categoria/{slug}', 'en'));
        $this->assertSame('/servicos/consultoria-empresarial', Localization::path('/servicos/consultoria-empresarial', 'pt'));
    }

    public function test_language_switcher_translates_fixed_paths(): void
    {
        $this->get('/sobre-nos')->assertSee('href="'.url('/en/about-us').'"', false);
        $this->get('/en/privacy-policy')->assertSee('href="'.url('/politica-de-privacidade').'"', false);
    }

    public function test_hreflang_lists_both_languages_and_x_default(): void
    {
        $this->get('/en/contact')
            ->assertSee('<link rel="alternate" hreflang="pt" href="'.url('/contactos').'">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/contact').'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/contactos').'">', false);
    }

    public function test_portuguese_segments_under_en_redirect_to_english_paths(): void
    {
        $this->get('/en/contactos')->assertStatus(301)->assertRedirect('/en/contact');
        $this->get('/en/servicos/consultoria-empresarial')->assertStatus(301)->assertRedirect('/en/services/consultoria-empresarial');
    }
}
