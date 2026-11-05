<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissões por área do painel e os dois perfis iniciais: Administrador
 * (acesso total) e Gestor de conteúdos (tudo menos Definições e
 * Utilizadores). Não altera perfis que já existam.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(Permissions::ALL) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::firstOrCreate(['name' => Permissions::ADMIN_ROLE, 'guard_name' => 'web']);
        $admin->syncPermissions(array_keys(Permissions::ALL));

        $manager = Role::where('name', Permissions::MANAGER_ROLE)->first();

        if (! $manager) {
            Role::create(['name' => Permissions::MANAGER_ROLE, 'guard_name' => 'web'])
                ->syncPermissions(Permissions::managerDefaults());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
