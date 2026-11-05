<?php

namespace Tests\Feature;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\Page;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class MenusTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    public function test_seeder_creates_one_menu_per_location_without_overwriting(): void
    {
        $this->seed(MenuSeeder::class);

        $this->assertSame(count(MenuLocation::cases()), Menu::count());

        $header = Menu::where('location', MenuLocation::Header)->first();
        $header->update(['items' => []]);

        $this->seed(MenuSeeder::class);

        $this->assertSame([], $header->fresh()->items);
    }

    public function test_header_uses_menu_edited_in_panel(): void
    {
        $page = Page::factory()->create(['slug' => 'historia']);

        Menu::factory()->location(MenuLocation::Header)->create(['items' => [
            ['label' => ['pt' => 'Parceiros', 'en' => 'Partners'], 'link' => ['type' => 'page', 'page_id' => $page->id]],
            ['label' => ['pt' => 'Loja', 'en' => 'Shop'], 'link' => ['type' => 'url', 'url' => 'https://loja.acb.co.mz', 'new_tab' => true], 'children' => [
                ['label' => ['pt' => 'Contactos'], 'link' => ['type' => 'route', 'route' => 'contact']],
            ]],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSee(url('/historia'), false)
            ->assertSee('target="_blank" rel="noopener" >Loja</a>', false)
            ->assertDontSee(url('/produtos/inexistente'), false);

        $this->get('/en')
            ->assertSee('Partners')
            ->assertSee(url('/en/historia'), false)
            ->assertSee('Contactos'); // sem tradução usa o português
    }

    public function test_items_with_unpublished_pages_are_skipped(): void
    {
        $draft = Page::factory()->draft()->create(['slug' => 'escondida']);

        Menu::factory()->location(MenuLocation::Header)->create(['items' => [
            ['label' => ['pt' => 'Escondida'], 'link' => ['type' => 'page', 'page_id' => $draft->id]],
        ]]);

        $this->get('/')->assertDontSee('Escondida</a>', false);
    }

    public function test_footer_menus_come_from_panel(): void
    {
        Menu::factory()->location(MenuLocation::FooterServices)->create(['items' => [
            ['label' => ['pt' => 'Websites Profissionais'], 'link' => ['type' => 'route', 'route' => 'services']],
        ]]);

        Menu::factory()->location(MenuLocation::Legal)->create(['items' => [
            ['label' => ['pt' => 'Livro de reclamações'], 'link' => ['type' => 'url', 'url' => 'https://www.livroreclamacoes.mz']],
        ]]);

        $this->get('/')
            ->assertSee('Websites Profissionais')
            ->assertSee('Livro de reclamações');
    }

    public function test_parent_is_active_on_child_pages(): void
    {
        $this->seed(MenuSeeder::class);

        $this->get('/servicos/centro-de-avaliacao')
            ->assertSee('<li class="has-children is-active">', false);
    }
}
