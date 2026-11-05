<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Cria (ou mantém) a conta de administração do painel.
     */
    public function run(): void
    {
        $this->callSilent(RolesSeeder::class);

        $email = config('site.admin.email');

        $existing = User::where('email', $email)->first();

        if ($existing) {
            // Instalações anteriores aos perfis: garante o perfil Administrador.
            $existing->assignRole(Permissions::ADMIN_ROLE);
            $this->command?->info("Administrador {$email} já existe.");

            return;
        }

        $password = config('site.admin.password') ?: Str::password(16);

        User::create([
            'name' => config('site.admin.name'),
            'email' => $email,
            'password' => $password,
        ])->forceFill(['email_verified_at' => now()])->assignRole(Permissions::ADMIN_ROLE)->save();

        if (! config('site.admin.password')) {
            $this->command?->warn("Administrador {$email} criado com palavra-passe gerada: {$password}");
        } else {
            $this->command?->info("Administrador {$email} criado.");
        }
    }
}
