<?php

namespace Tests\Feature;

use App\Enums\ServiceKind;
use App\Models\Page;
use App\Models\Service;
use App\Support\Links;
use Database\Seeders\CatalogPagesSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCatalog;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase, SeedsCatalog;

    public function test_kinds_do_not_mix_and_unpublished_items_404(): void
    {
        $this->get('/servicos/inexistente')->assertNotFound();
        $this->get('/produtos/dominios')->assertNotFound();

        Service::where('slug', 'inexistente')->update(['is_published' => false]);

        $this->get('/produtos/inexistente')->assertNotFound();
        $this->assertNull(Links::url(['type' => 'route', 'route' => 'products.show|inexistente']));
    }

    public function test_header_submenus_come_from_catalog(): void
    {
        Service::factory()->create(['slug' => 'cloud', 'title' => ['pt' => 'Alojamento Cloud']]);

        $this->get('/')
            ->assertSee('Alojamento Cloud')
            ->assertSee(url('/servicos/cloud'), false);
    }

}
