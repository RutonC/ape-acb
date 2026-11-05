<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+258 84 000 0000',
            'message' => fake()->sentence(),
            'cv_path' => 'candidaturas/cv-'.fake()->uuid().'.pdf',
            'cv_original_name' => 'cv.pdf',
            'locale' => 'pt',
            'status' => ApplicationStatus::New,
        ];
    }
}
