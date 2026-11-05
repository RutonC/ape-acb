<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Service;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function general(array $overrides = []): array
    {
        $current = $this->getJson('/api/admin/settings/general')->assertOk()->json('data');

        // Listas (telefones) substituem-se por inteiro; o resto junta-se por chave.
        $lists = array_intersect_key($overrides, ['phones' => true]);
        $overrides = ['email' => 'geral@acb.co.mz', 'forms_recipient_email' => 'geral@acb.co.mz', 'hours' => ['pt' => 'Segunda a sexta'], 'phones' => ['+258 84 111 2222'], ...$overrides];
        $lists = array_intersect_key($overrides, ['phones' => true]);

        return [...array_replace_recursive($current, array_diff_key($overrides, $lists)), ...$lists];
    }

    public function test_general_settings_load_the_seeded_values(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/settings/general')
            ->assertOk()
            ->assertJsonPath('data.site_name', 'APE, Agência Privada de Emprego')
            ->assertJsonPath('data.address', 'Beira, Sofala, Moçambique');
    }

    public function test_general_settings_are_saved(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->putJson('/api/admin/settings/general', $this->general([
            'email' => 'geral@acb.co.mz',
            'hours' => ['pt' => 'Segunda a sexta', 'en' => 'Mon to Fri'],
            'linkedin' => 'https://linkedin.com/company/acb',
            'phones' => ['+258 84 111 2222'],
        ]))->assertOk()->assertJsonPath('data.email', 'geral@acb.co.mz');

        $settings = app(GeneralSettings::class)->refresh();
        $this->assertSame('geral@acb.co.mz', $settings->email);
        $this->assertSame('Mon to Fri', $settings->hours['en']);
        $this->assertSame('https://linkedin.com/company/acb', $settings->linkedin);
        $this->assertSame(['+258 84 111 2222'], $settings->phones);
    }

    public function test_general_settings_validate_required_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->putJson('/api/admin/settings/general', $this->general([
            'email' => 'nao-e-email',
            'tagline' => ['pt' => ''],
            'phones' => [],
            'facebook' => 'sem-url',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['email', 'tagline.pt', 'phones', 'facebook']);
    }

    public function test_cleared_optional_fields_are_stored_empty(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->putJson('/api/admin/settings/general', $this->general(['whatsapp' => null, 'facebook' => '', 'tagline' => ['en' => '']]))->assertOk();

        $settings = app(GeneralSettings::class)->refresh();
        $this->assertNull($settings->whatsapp);
        $this->assertNull($settings->facebook);
        $this->assertSame('', $settings->tagline['en']);
    }

    public function test_seo_settings_validate_ga4_id_and_save(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $seo = $this->getJson('/api/admin/settings/seo')->assertOk()->json('data');

        $this->putJson('/api/admin/settings/seo', [...$seo, 'ga4_measurement_id' => 'UA-123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ga4_measurement_id');

        $this->putJson('/api/admin/settings/seo', [...$seo, 'ga4_measurement_id' => 'G-ABC123', 'google_site_verification' => 'abc-verify'])->assertOk();

        $settings = app(SeoSettings::class)->refresh();
        $this->assertSame('G-ABC123', $settings->ga4_measurement_id);
        $this->assertSame('abc-verify', $settings->google_site_verification);
    }

    public function test_manager_cannot_reach_settings(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->getJson('/api/admin/settings/general')->assertForbidden();
        $this->putJson('/api/admin/settings/seo', [])->assertForbidden();
    }

    public function test_dashboard_lists_untranslated_content_with_links_and_respects_areas(): void
    {
        $this->seed(RolesSeeder::class);
        $service = Service::factory()->create(['title' => ['pt' => 'Consultoria'], 'summary' => ['pt' => 'Resumo']]);
        $page = Page::factory()->create(['title' => ['pt' => 'Sobre']]);

        $this->actingAs(User::factory()->admin()->create());
        $items = collect($this->getJson('/api/admin/dashboard')->assertOk()->json('data.translations'));
        $this->assertSame("/servicos/{$service->id}", $items->firstWhere('title', 'Consultoria')['path']);
        $this->assertSame("/paginas/{$page->id}", $items->firstWhere('title', 'Sobre')['path']);

        // Perfil só com a área de páginas: não vê serviços.
        $role = Role::findOrCreate('Editor de páginas', 'web');
        $role->givePermissionTo('pages');
        $editor = User::factory()->create();
        $editor->assignRole($role);
        $this->actingAs($editor);

        $titles = collect($this->getJson('/api/admin/dashboard')->json('data.translations'))->pluck('title');
        $this->assertTrue($titles->contains('Sobre'));
        $this->assertFalse($titles->contains('Consultoria'));
    }

    public function test_dashboard_shows_counts_and_recent_inbox_only_for_allowed_areas(): void
    {
        $this->seed(RolesSeeder::class);
        ContactMessage::factory()->count(2)->create();

        $this->actingAs(User::factory()->admin()->create());
        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.counts.messages.count', 2)
            ->assertJsonCount(2, 'data.messages')
;

        $role = Role::findOrCreate('Recepção', 'web');
        $role->givePermissionTo('messages');
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonCount(2, 'data.messages')
;
    }

    public function test_guests_cannot_reach_the_dashboard(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }
}
