<?php

namespace Database\Factories;

use App\Enums\MenuLocation;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => MenuLocation::Header,
            'name' => 'Menu principal',
            'items' => [
                ['label' => ['pt' => 'Início', 'en' => 'Home'], 'link' => ['type' => 'route', 'route' => 'home']],
            ],
        ];
    }

    public function location(MenuLocation $location): static
    {
        return $this->state(['location' => $location, 'name' => $location->getLabel()]);
    }
}
