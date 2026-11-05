<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => ['pt' => 'Gestor de projectos'],
            'bio' => ['pt' => fake()->sentence()],
            'is_published' => true,
        ];
    }
}
