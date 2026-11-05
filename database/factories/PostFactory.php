<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'slug' => Str::slug($title),
            'title' => ['pt' => $title],
            'excerpt' => ['pt' => fake()->sentence(12)],
            'content' => ['pt' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>'],
            'status' => PageStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PageStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => PageStatus::Published, 'published_at' => now()->addWeek()]);
    }
}
