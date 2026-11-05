<?php

namespace Tests\Concerns;

use Database\Seeders\ServiceSeeder;

/**
 * Cria o catálogo de serviços e produtos antes de cada teste
 * (menus, ligações e páginas de detalhe dependem dele).
 */
trait SeedsCatalog
{
    protected function setUpSeedsCatalog(): void
    {
        $this->seed(ServiceSeeder::class);
    }
}
