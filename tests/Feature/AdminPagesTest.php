<?php

namespace Tests\Feature;

use App\Enums\MenuLocation;
use App\Enums\PageStatus;
use App\Models\Menu;
use App\Models\Page;
use App\Models\User;
use App\Support\PageBlocks;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'slug' => 'parcerias',
            'title' => ['pt' => 'Parcerias'],
            'status' => 'published',
            'is_homepage' => false,
            'content' => [
                'pt' => [
                    ['type' => 'hero', 'data' => [
                        'title' => 'Trabalhe connosco',
                        'primary_button' => ['label' => 'Contactar', 'type' => 'route', 'route' => 'contact', 'page_id' => 9, 'url' => 'x'],
                        'campo_estranho' => 'não guarda',
                    ]],
                    ['type' => 'faq', 'data' => [
                        'title' => 'Dúvidas',
                        'items' => [['question' => 'Como?', 'answer' => 'Assim.', '_key' => 'abc']],
                        'background' => 'light',
                        'is_active' => false,
                    ]],
                    ['type' => 'about', 'data' => ['bullets' => ['Um', '', ' Dois ']]],
                ],
                'en' => [],
            ],
        ], $overrides);
    }

    public function test_block_definitions_cover_every_block_view(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $blocks = $this->getJson('/api/admin/pages/blocks')->assertOk()->json('data');

        $this->assertSame(PageBlocks::TYPES, array_column($blocks, 'type'));
        foreach ($blocks as $block) {
            $this->assertTrue(view()->exists(PageBlocks::view($block['type'])), $block['type']);
            $this->assertNotEmpty($block['fields']);
        }
        $this->assertFalse(collect($blocks)->firstWhere('type', 'pricing')['active']);
    }

    public function test_admin_creates_a_page_and_blocks_are_cleaned(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $id = $this->postJson('/api/admin/pages', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.blocks_count.pt', 3)
            ->assertJsonPath('data.content.en', null)
            ->json('data.id');

        $blocks = Page::find($id)->getTranslation('content', 'pt');
        $this->assertArrayNotHasKey('campo_estranho', $blocks[0]['data']);
        $this->assertSame(['label' => 'Contactar', 'type' => 'route', 'route' => 'contact', 'page_id' => null, 'url' => null, 'new_tab' => false], $blocks[0]['data']['primary_button']);
        $this->assertSame('white', $blocks[0]['data']['background']);
        $this->assertTrue($blocks[0]['data']['is_active']);
        $this->assertSame([['question' => 'Como?', 'answer' => 'Assim.']], $blocks[1]['data']['items']);
        $this->assertFalse($blocks[1]['data']['is_active']);
        $this->assertSame(['Um', 'Dois'], $blocks[2]['data']['bullets']);

        // Bloco oculto não aparece no site.
        $this->get('/parcerias')->assertOk()->assertSee('Trabalhe connosco')->assertDontSee('Como?');
    }

    public function test_block_errors_point_to_the_field(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->postJson('/api/admin/pages', $this->payload(['content' => ['pt' => [
            ['type' => 'hero', 'data' => ['title' => '']],
            ['type' => 'faq', 'data' => ['items' => [['question' => 'Só pergunta']]]],
            ['type' => 'cards', 'data' => ['columns' => 7, 'items' => [['title' => 'X', 'image' => 999]]]],
            ['type' => 'inexistente', 'data' => []],
        ]]]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content.pt.0.data.title',
                'content.pt.1.data.items.0.answer',
                'content.pt.2.data.columns',
                'content.pt.2.data.items.0.image',
                'content.pt.3.type',
            ]);
    }

    public function test_slug_must_be_unique_and_not_reserved(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Page::factory()->create(['slug' => 'parcerias']);

        $this->postJson('/api/admin/pages', $this->payload())->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/pages', $this->payload(['slug' => 'admin']))->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/pages', $this->payload(['slug' => 'Com Espaço']))->assertJsonValidationErrors('slug');
    }

    public function test_edit_returns_every_translation_and_only_one_homepage_is_kept(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $old = Page::factory()->create(['is_homepage' => true, 'status' => PageStatus::Published]);
        $page = Page::factory()->create([
            'title' => ['pt' => 'Início novo', 'en' => 'New home'],
            'content' => ['pt' => [['type' => 'cta', 'data' => ['title' => 'Olá']]], 'en' => [['type' => 'cta', 'data' => ['title' => 'Hello']]]],
        ]);

        $this->getJson("/api/admin/pages/{$page->id}")
            ->assertJsonPath('data.title.en', 'New home')
            ->assertJsonPath('data.content.en.0.data.title', 'Hello');

        $this->putJson("/api/admin/pages/{$page->id}", [
            'slug' => $page->slug, 'status' => 'published', 'is_homepage' => true,
            'title' => ['pt' => 'Início novo', 'en' => 'New home'],
            'content' => ['pt' => [['type' => 'cta', 'data' => ['title' => 'Olá']]], 'en' => []],
        ])->assertOk()->assertJsonPath('data.is_homepage', true);

        $this->assertFalse($old->fresh()->is_homepage);
        // Lista inglesa vazia: o site volta a usar os blocos portugueses.
        $this->assertSame([], $page->fresh()->getTranslation('content', 'en', false) ?: []);
    }

    public function test_trash_restore_force_and_homepage_protection(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $home = Page::factory()->create(['is_homepage' => true]);
        $page = Page::factory()->create();

        $this->deleteJson("/api/admin/pages/{$home->id}")->assertUnprocessable();
        $this->deleteJson("/api/admin/pages/{$page->id}")->assertNoContent();
        $this->getJson('/api/admin/pages?filter[trashed]=only')->assertJsonCount(1, 'data')->assertJsonPath('data.0.state', 'trashed');
        $this->postJson("/api/admin/pages/{$page->id}/restore")->assertOk()->assertJsonPath('data.state', fn ($state) => $state !== 'trashed');
        $this->deleteJson("/api/admin/pages/{$page->id}/force")->assertNoContent();
        $this->assertModelMissing($page);
    }

    public function test_preview_shows_drafts(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $page = Page::factory()->create(['status' => PageStatus::Draft, 'slug' => 'rascunho', 'content' => ['pt' => [['type' => 'cta', 'data' => ['title' => 'Texto em rascunho']]]]]);

        $url = $this->getJson("/api/admin/pages/{$page->id}/preview?locale=pt")->json('url');

        $this->get($url)->assertOk()->assertSee('Texto em rascunho');
        $this->get('/rascunho')->assertNotFound();
    }

    public function test_admin_edits_menu_items_and_submenus_only_in_the_header(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
        $header = Menu::where('location', MenuLocation::Header)->first();
        $footer = Menu::where('location', MenuLocation::Footer)->first();

        $this->getJson('/api/admin/menus')->assertOk()->assertJsonCount(count(MenuLocation::cases()), 'data')->assertJsonPath('data.0.location', 'header');

        $item = fn (string $label, array $children = []) => [
            'label' => ['pt' => $label, 'en' => ''],
            'link' => ['type' => 'url', 'url' => 'https://acb.co.mz', 'route' => 'home', 'new_tab' => true],
            'children' => $children,
        ];

        $this->putJson("/api/admin/menus/{$header->id}", ['name' => 'Principal', 'items' => [$item('Empresa', [$item('Equipa')])]])
            ->assertOk()
            ->assertJsonPath('data.items.0.children.0.label.pt', 'Equipa')
            ->assertJsonPath('data.items.0.link.route', null);
        $this->assertSame(['pt' => 'Empresa'], $header->fresh()->items[0]['label']);

        $this->putJson("/api/admin/menus/{$footer->id}", ['name' => 'Rodapé', 'items' => [$item('Empresa', [$item('Equipa')])]])
            ->assertJsonPath('data.items.0.children', []);

        $this->putJson("/api/admin/menus/{$footer->id}", ['name' => 'Rodapé', 'items' => [$item('')]])->assertJsonValidationErrors('items.0.label.pt');

        $this->get('/')->assertSee('Empresa');
    }

    public function test_pages_and_menus_need_their_areas(): void
    {
        $this->seed(RolesSeeder::class);
        $role = Role::create(['name' => 'Só menus', 'guard_name' => 'web'])->givePermissionTo('menus');
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->getJson('/api/admin/pages')->assertForbidden();
        $this->getJson('/api/admin/pages/blocks')->assertForbidden();
        $this->getJson('/api/admin/menus')->assertOk();
    }
}
