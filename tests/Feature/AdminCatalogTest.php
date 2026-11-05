<?php

namespace Tests\Feature;

use App\Enums\ServiceKind;
use App\Models\Media;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Links;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function media(): Media
    {
        return Media::create([
            'disk' => 'public', 'directory' => 'media', 'name' => 'foto', 'path' => 'media/foto.jpg',
            'type' => 'image/jpeg', 'ext' => 'jpg', 'width' => 800, 'height' => 600, 'size' => 100,
        ]);
    }

    private function servicePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'slug' => 'consultoria',
            'title' => ['pt' => 'Consultoria', 'en' => ''],
            'summary' => ['pt' => 'Apoio técnico', 'en' => 'Technical support'],
            'details' => ['pt' => [['title' => 'O que inclui', 'icon' => 'wrench-screwdriver', 'content' => '<p>Análise</p>']], 'en' => []],
            'icon' => 'code-bracket-square',
            'is_published' => true,
        ], $overrides);
    }

    public function test_admin_creates_and_edits_services_keeping_translations(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $id = $this->postJson('/api/admin/services', $this->servicePayload())
            ->assertCreated()
            ->assertJsonPath('data.kind', 'service')
            ->assertJsonPath('data.title.pt', 'Consultoria')
            ->assertJsonPath('data.title.en', null)
            ->assertJsonPath('data.translation.complete', false)
            ->json('data.id');

        $service = Service::find($id);
        $this->assertSame(['pt' => 'Consultoria'], $service->getTranslations('title'));
        $this->assertSame('O que inclui', $service->detailSections()[0]['title']);

        // Apagar o texto inglês remove a tradução.
        $this->putJson("/api/admin/services/{$id}", $this->servicePayload(['title' => ['en' => 'Consulting'], 'summary' => ['en' => '']]))
            ->assertOk()
            ->assertJsonPath('data.title.en', 'Consulting');
        $this->assertSame(['pt' => 'Apoio técnico'], $service->fresh()->getTranslations('summary'));
    }

    public function test_slug_is_unique_per_kind_and_validated(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Service::factory()->create(['kind' => ServiceKind::Service, 'slug' => 'consultoria']);

        $this->postJson('/api/admin/services', $this->servicePayload())->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/services', $this->servicePayload(['slug' => 'Com Espaços']))->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/services', $this->servicePayload(['slug' => 'outro', 'icon' => 'nao-existe']))->assertJsonValidationErrors('icon');
        $this->postJson('/api/admin/services', $this->servicePayload(['slug' => 'outro', 'title' => ['pt' => '']]))->assertJsonValidationErrors('title.pt');
    }

    public function test_saving_refreshes_the_catalog_links(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $service = Service::factory()->create(['kind' => ServiceKind::Service, 'slug' => 'consultoria', 'title' => ['pt' => 'Consultoria']]);
        $this->assertArrayHasKey('services.show|consultoria', Links::routeOptions());

        $this->putJson("/api/admin/services/{$service->id}", $this->servicePayload(['slug' => 'consultoria-ti']))->assertOk();

        $this->assertArrayHasKey('services.show|consultoria-ti', Links::routeOptions());
    }

    public function test_reorder_bulk_delete_and_new_items_go_last(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        [$a, $b, $c] = Service::factory()->count(3)->sequence(
            ['slug' => 'a', 'sort_order' => 1], ['slug' => 'b', 'sort_order' => 2], ['slug' => 'c', 'sort_order' => 3],
        )->create(['kind' => ServiceKind::Service]);

        $this->postJson('/api/admin/services/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertNoContent();
        $this->assertSame(['c', 'a', 'b'], Service::orderBy('sort_order')->pluck('slug')->all());

        $this->postJson('/api/admin/services', $this->servicePayload(['slug' => 'novo']))->assertJsonPath('data.sort_order', 4);

        $this->postJson('/api/admin/services/bulk-delete', ['ids' => [$a->id, $b->id]])->assertNoContent();
        $this->assertSame(['c', 'novo'], Service::orderBy('sort_order')->pluck('slug')->all());
    }

    public function test_each_area_needs_its_permission(): void
    {
        $this->seed(RolesSeeder::class);
        $role = Role::create(['name' => 'Só serviços', 'guard_name' => 'web']);
        $role->givePermissionTo('services');
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->getJson('/api/admin/services')->assertOk();
        $this->getJson('/api/admin/jobs')->assertForbidden();
        $this->getJson('/api/admin/applications')->assertForbidden();
        $this->getJson('/api/admin/team')->assertForbidden();
    }



    public function test_admin_manages_team(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $id = $this->postJson('/api/admin/team', [
            'name' => 'Ana Mendes',
            'role' => ['pt' => 'Directora técnica', 'en' => 'Technical director'],
            'linkedin_url' => 'https://www.linkedin.com/in/ana',
            'is_published' => true,
        ])->assertCreated()->assertJsonPath('data.translation.complete', true)->json('data.id');

        $this->postJson('/api/admin/team', ['name' => 'X', 'role' => ['pt' => 'Y'], 'linkedin_url' => 'nao-e-url'])->assertJsonValidationErrors('linkedin_url');

        $this->putJson("/api/admin/team/{$id}", ['name' => 'Ana M.', 'role' => ['pt' => 'Directora']])->assertOk()->assertJsonPath('data.role.en', null);
        $this->getJson('/api/admin/team?search=Ana')->assertJsonCount(1, 'data');
        $this->deleteJson("/api/admin/team/{$id}")->assertNoContent();
        $this->assertSame(0, TeamMember::count());
    }

    public function test_option_lists(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        Service::factory()->create(['kind' => ServiceKind::Service, 'title' => ['pt' => 'Académico']]);

        $this->getJson('/api/admin/options/icons')->assertOk()->assertJsonPath('data.0.svg', fn ($svg) => str_starts_with($svg, '<svg'));
        $this->getJson('/api/admin/options/services')->assertJsonPath('data.0.label', 'Serviço: Académico');
    }
}
