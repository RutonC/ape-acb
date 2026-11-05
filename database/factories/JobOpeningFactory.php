<?php

namespace Database\Factories;

use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Desenvolvedor '.fake()->unique()->word();

        return [
            'slug' => Str::slug($title),
            'title' => ['pt' => $title],
            'contract_type' => ['pt' => 'Tempo inteiro'],
            'location' => ['pt' => 'Beira'],
            'summary' => ['pt' => fake()->sentence()],
            'description' => ['pt' => '<p>'.fake()->paragraph().'</p>'],
            'requirements' => ['pt' => '<ul><li>'.fake()->sentence().'</li></ul>'],
            'is_open' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(['is_open' => false]);
    }
}
