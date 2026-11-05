<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\Links;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    private function sampleBlocks(): array
    {
        return [
            ['type' => 'hero', 'data' => ['title' => 'Título do destaque', 'is_active' => true, 'primary_button' => ['label' => 'Fale connosco', 'type' => 'route', 'route' => 'contact']]],
            ['type' => 'cards', 'data' => ['title' => 'O que fazemos', 'style' => 'service', 'columns' => 3, 'is_active' => true, 'items' => [
                ['icon' => 'globe-alt', 'title' => 'Websites Profissionais', 'text' => 'Sites rápidos.', 'link' => ['type' => 'route', 'route' => 'services.show|dominios']],
            ]]],
            ['type' => 'counters', 'data' => ['is_active' => true, 'items' => [['prefix' => '+', 'value' => 20, 'label' => 'projectos entregues']]]],
            ['type' => 'process', 'data' => ['is_active' => true, 'steps' => [['title' => 'Ouvimos'], ['title' => 'Propomos']]]],
            ['type' => 'cta', 'data' => ['style' => 'call', 'title' => 'Tem um projecto?', 'is_active' => true]],
            ['type' => 'faq', 'data' => ['is_active' => true, 'items' => [['question' => 'Quanto custa?', 'answer' => 'Depende do âmbito.']]]],
            ['type' => 'pricing', 'data' => ['title' => 'Planos secretos', 'is_active' => false, 'plans' => [['name' => 'Pro', 'price' => '100']]]],
        ];
    }

    public function test_published_page_renders_its_blocks(): void
    {
        Page::factory()->withBlocks($this->sampleBlocks())->create(['slug' => 'solucoes']);

        $this->get('/solucoes')
            ->assertOk()
            ->assertSee('Título do destaque')
            ->assertSee('Websites Profissionais')
            ->assertSee(url('/servicos/centro-de-avaliacao'), false)
            ->assertSee('projectos entregues')
            ->assertSeeInOrder(['1.', 'Ouvimos', '2.', 'Propomos'])
            ->assertSee('Quanto custa?')
            ->assertDontSee('Planos secretos');
    }

    public function test_page_without_hero_shows_banner_with_title(): void
    {
        Page::factory()->withBlocks([
            ['type' => 'rich_text', 'data' => ['content' => '<p>Texto da política</p>', 'is_active' => true]],
        ])->create(['slug' => 'faq-geral', 'title' => ['pt' => 'Perguntas gerais']]);

        $this->get('/faq-geral')
            ->assertOk()
            ->assertSee('page-banner', false)
            ->assertSee('Perguntas gerais')
            ->assertSee('Texto da política');
    }

    public function test_draft_page_is_hidden_but_available_through_signed_preview(): void
    {
        $page = Page::factory()->draft()->withBlocks($this->sampleBlocks())->create(['slug' => 'rascunho']);

        $this->get('/rascunho')->assertNotFound();

        $this->get($page->previewUrl())
            ->assertOk()
            ->assertSee('Título do destaque')
            ->assertSee('noindex', false);

        $this->get(route('pages.preview', ['page' => $page, 'locale' => 'pt']))->assertForbidden();
    }

    public function test_english_version_falls_back_to_portuguese_blocks(): void
    {
        Page::factory()->withBlocks($this->sampleBlocks())->create(['slug' => 'solucoes']);

        $this->get('/en/solucoes')->assertOk()->assertSee('Título do destaque');
    }

    public function test_english_blocks_are_used_when_translated(): void
    {
        Page::factory()
            ->withBlocks($this->sampleBlocks())
            ->withBlocks([['type' => 'hero', 'data' => ['title' => 'Hero title', 'is_active' => true]]], 'en')
            ->create(['slug' => 'solucoes']);

        $this->get('/en/solucoes')->assertSee('Hero title')->assertDontSee('Título do destaque');
    }

    public function test_homepage_page_replaces_provisional_home(): void
    {
        Page::factory()->withBlocks($this->sampleBlocks())->create(['slug' => 'inicio', 'is_homepage' => true]);

        $this->get('/')->assertOk()->assertSee('Título do destaque');
        $this->get('/inicio')->assertNotFound();
    }

    public function test_only_one_homepage_is_kept(): void
    {
        $first = Page::factory()->create(['is_homepage' => true]);
        Page::factory()->create(['is_homepage' => true]);

        $this->assertFalse($first->fresh()->is_homepage);
    }

    public function test_fixed_route_uses_page_with_the_same_slug(): void
    {
        $this->get('/sobre-nos')->assertSee('Página em preparação');

        Page::factory()->withBlocks([
            ['type' => 'hero', 'data' => ['title' => 'Engenharia de software feita em Moçambique', 'is_active' => true]],
        ])->create(['slug' => 'sobre-nos']);

        $this->get('/sobre-nos')->assertOk()->assertSee('Engenharia de software feita em Moçambique');
    }

}
