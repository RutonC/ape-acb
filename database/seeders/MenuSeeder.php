<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Support\Navigation;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Cria os menus a partir da estrutura inicial (não altera menus já editados).
     */
    public function run(): void
    {
        foreach (MenuLocation::cases() as $location) {
            Menu::firstOrCreate(
                ['location' => $location],
                ['name' => $location->getLabel(), 'items' => Navigation::defaultItems($location)],
            );
        }
    }
}
