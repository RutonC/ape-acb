<?php

namespace Database\Factories;

use App\Enums\InboxStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'organization' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => '+258 84 000 0000',
            'service' => 'recrutamento-e-seleccao',
            'message' => fake()->paragraph(),
            'locale' => 'pt',
            'status' => InboxStatus::New,
        ];
    }
}
