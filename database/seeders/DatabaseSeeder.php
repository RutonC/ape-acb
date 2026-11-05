<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            AdminUserSeeder::class,
            ServiceSeeder::class,
            MenuSeeder::class,
            NewsSeeder::class,
            JobSeeder::class,
            HomePageSeeder::class,
            InstitutionalPagesSeeder::class,
        ]);
    }
}
