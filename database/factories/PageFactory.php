<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => ['pt' => $title, 'en' => $title],
            'slug' => Str::slug($title),
            'content' => ['pt' => [], 'en' => []],
            'status' => PageStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PageStatus::Draft, 'published_at' => null]);
    }

    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $blocks
     */
    public function withBlocks(array $blocks, string $locale = 'pt'): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => [...($attributes['content'] ?? []), $locale => $blocks],
        ]);
    }
}
