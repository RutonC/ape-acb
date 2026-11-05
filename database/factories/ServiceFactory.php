<?php

namespace Database\Factories;

use App\Enums\ServiceKind;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'kind' => ServiceKind::Service,
            'slug' => Str::slug($title),
            'title' => ['pt' => $title],
            'summary' => ['pt' => fake()->sentence()],
            'intro' => ['pt' => fake()->paragraph()],
            'details' => ['pt' => [
                ['title' => 'O que inclui', 'icon' => 'check-badge', 'content' => '<p>'.fake()->sentence().'</p>'],
            ]],
            'icon' => 'squares-2x2',
            'is_published' => true,
        ];
    }


    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
