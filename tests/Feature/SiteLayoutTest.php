<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Service;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class SiteLayoutTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    public function test_header_shows_contacts_menu_and_call_to_action(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Início', 'Vagas', 'Serviços', 'Notícias', 'Sobre Nós', 'Contactos', 'Visitar a ACB'])
            ->assertSee(url('/candidatura'), false)
            ->assertSee(url('/empresas'), false)
            ->assertSee('Candidate-se já')
            ->assertSee('https://www.acb.co.mz', false);
    }

    public function test_current_menu_item_is_marked_active(): void
    {
        $this->get('/servicos/centro-de-avaliacao')
            ->assertSee('<li class="has-children is-active">', false)
            ->assertSee('<li class="is-active">', false);
    }

    public function test_inner_pages_have_banner_with_breadcrumbs(): void
    {
        $this->get('/servicos/centro-de-avaliacao')
            ->assertSee('page-banner', false)
            ->assertSeeInOrder(['Início', 'Serviços', 'Centro de Avaliação']);
    }

    public function test_footer_has_legal_links_and_copyright(): void
    {
        $this->get('/')
            ->assertSee('Associação Comercial da Beira. Todos os direitos reservados.')
            ->assertSee(url('/politica-de-privacidade'), false)
            ->assertSee(url('/termos-de-utilizacao'), false);
    }

    public function test_whatsapp_button_and_cookie_consent_are_present(): void
    {
        $this->get('/')
            ->assertSee('x-data="cookieConsent"', false);
    }

    public function test_layout_uses_contacts_and_social_links_from_settings(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->facebook = 'https://facebook.com/acb';
        $settings->email = 'geral@acb.co.mz';
        $settings->whatsapp = null;
        $settings->save();

        $this->get('/')
            ->assertSee('https://facebook.com/acb', false)
            ->assertSee('geral@acb.co.mz')
            ->assertDontSee('https://wa.me/', false);
    }

    public function test_logo_and_favicon_can_come_from_media_library(): void
    {
        $media = Media::create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'logo-novo',
            'path' => 'media/logo-novo.png',
            'ext' => 'png',
            'type' => 'image/png',
            'size' => 1024,
            'width' => 400,
            'height' => 100,
        ]);

        $settings = app(GeneralSettings::class);
        $settings->logo_id = $media->id;
        $settings->favicon_id = $media->id;
        $settings->save();

        $this->get('/')
            ->assertSee('media/logo-novo.png', false)
            ->assertDontSee('favicon.svg', false);
    }

}
