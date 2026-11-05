<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->withRole(Permissions::ADMIN_ROLE);
    }

    public function manager(): static
    {
        return $this->withRole(Permissions::MANAGER_ROLE);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    private function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            if (! Role::where('name', $role)->exists()) {
                (new RolesSeeder)->run();
            }

            $user->assignRole($role);
        });
    }
}
