<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
    }

    private function role(string $name): Role
    {
        return Role::findByName($name, 'web');
    }

    public function test_seeder_creates_admin_and_manager_roles(): void
    {
        $this->assertCount(count(Permissions::ALL), $this->role(Permissions::ADMIN_ROLE)->permissions);

        $manager = $this->role(Permissions::MANAGER_ROLE)->permissions->pluck('name');
        $this->assertContains('pages', $manager);
        $this->assertNotContains('settings', $manager);
        $this->assertNotContains('users', $manager);
    }

    public function test_session_reports_the_areas_each_profile_reaches(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/me')
            ->assertJsonPath('data.is_admin', true)
            ->assertJsonPath('data.areas', array_keys(Permissions::ALL));

        $areas = $this->actingAs(User::factory()->manager()->create())->getJson('/api/admin/me')->json('data.areas');
        $this->assertContains('blog', $areas);
        $this->assertNotContains('users', $areas);
        $this->assertNotContains('settings', $areas);
    }

    public function test_manager_cannot_manage_users_or_roles(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/roles')->assertForbidden();
    }

    public function test_custom_role_only_reaches_its_areas(): void
    {
        Role::create(['name' => 'Recursos humanos', 'guard_name' => 'web'])->syncPermissions(['messages', 'users']);
        $user = User::factory()->create();
        $user->assignRole('Recursos humanos');

        $this->actingAs($user)->getJson('/api/admin/me')->assertJsonPath('data.areas', ['messages', 'users']);
        $this->getJson('/api/admin/users')->assertOk();
    }

    public function test_admin_lists_and_filters_users(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'Ana Admin', 'email' => 'ana@acb.co.mz']));
        User::factory()->manager()->create(['name' => 'Bruno Gestor', 'email' => 'bruno@acb.co.mz']);

        $this->getJson('/api/admin/users?search=bruno')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.roles.0.name', Permissions::MANAGER_ROLE)
            ->assertJsonPath('data.0.two_factor_enabled', false);

        $this->getJson('/api/admin/users?filter[role]='.$this->role(Permissions::ADMIN_ROLE)->id)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_self', true);
    }

    public function test_admin_creates_a_manager(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->postJson('/api/admin/users', [
            'name' => 'Carla Conteúdos',
            'email' => 'carla@acb.co.mz',
            'password' => 'Palavra-Forte-2026',
            'password_confirmation' => 'Palavra-Forte-2026',
            'roles' => [$this->role(Permissions::MANAGER_ROLE)->id],
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.roles.0.name', Permissions::MANAGER_ROLE);

        $carla = User::where('email', 'carla@acb.co.mz')->first();
        $this->assertTrue($carla->hasRole(Permissions::MANAGER_ROLE));
        $this->assertTrue(Hash::check('Palavra-Forte-2026', $carla->password));
    }

    public function test_password_is_required_on_create_and_kept_when_blank_on_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $roles = [$this->role(Permissions::MANAGER_ROLE)->id];

        $this->postJson('/api/admin/users', ['name' => 'Sem Palavra', 'email' => 'sem@acb.co.mz', 'roles' => $roles])
            ->assertJsonValidationErrors('password');

        $user = User::factory()->manager()->create();
        $hash = $user->password;

        $this->putJson("/api/admin/users/{$user->id}", ['name' => 'Novo Nome', 'email' => $user->email, 'password' => '', 'roles' => $roles])
            ->assertOk();

        $this->assertSame('Novo Nome', $user->fresh()->name);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_last_administrator_cannot_lock_themselves_out(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $managerRole = [$this->role(Permissions::MANAGER_ROLE)->id];

        $this->putJson("/api/admin/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'roles' => $managerRole])
            ->assertJsonValidationErrors('roles');

        $this->putJson("/api/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'roles' => [$this->role(Permissions::ADMIN_ROLE)->id], 'is_active' => false,
        ])->assertJsonValidationErrors('is_active');

        $this->deleteJson("/api/admin/users/{$admin->id}")->assertUnprocessable();
        $this->assertTrue($admin->fresh()->isAdministrator());
    }

    public function test_admin_cannot_deactivate_themselves_but_can_delete_others(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $other = User::factory()->manager()->create();
        $this->actingAs($admin);

        $this->putJson("/api/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'roles' => [$this->role(Permissions::ADMIN_ROLE)->id], 'is_active' => false,
        ])->assertJsonValidationErrors('is_active');

        $this->deleteJson("/api/admin/users/{$other->id}")->assertNoContent();
        $this->assertModelMissing($other);
    }

    public function test_reset_two_factor_clears_the_secret(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->manager()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('SEGREDO'),
            'two_factor_recovery_codes' => encrypt(json_encode(['a'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->postJson("/api/admin/users/{$user->id}/reset-two-factor")
            ->assertOk()
            ->assertJsonPath('data.two_factor_enabled', false);

        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_admin_manages_roles_and_administrator_role_is_protected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/permissions')->assertOk()->assertJsonPath('data.0.group', 'Caixa de entrada');
        $this->getJson('/api/admin/roles')
            ->assertOk()
            ->assertJsonPath('data.0.name', Permissions::ADMIN_ROLE)
            ->assertJsonPath('data.0.users_count', 1)
            ->assertJsonPath('data.0.is_admin', true);

        $id = $this->postJson('/api/admin/roles', ['name' => 'Blog', 'permissions' => ['blog', 'media']])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/admin/roles', ['name' => 'Inválido', 'permissions' => ['nao-existe']])
            ->assertJsonValidationErrors('permissions.0');

        $this->putJson("/api/admin/roles/{$id}", ['name' => 'Blog e média', 'permissions' => ['blog']])
            ->assertOk()
            ->assertJsonPath('data.permissions', ['blog']);

        $admin = $this->role(Permissions::ADMIN_ROLE);
        $this->putJson("/api/admin/roles/{$admin->id}", ['name' => 'Outro', 'permissions' => []])->assertUnprocessable();
        $this->deleteJson("/api/admin/roles/{$admin->id}")->assertUnprocessable();

        $manager = $this->role(Permissions::MANAGER_ROLE);
        User::factory()->manager()->create();
        $this->deleteJson("/api/admin/roles/{$manager->id}")->assertUnprocessable();

        $this->deleteJson("/api/admin/roles/{$id}")->assertNoContent();
        $this->assertSame(Permissions::ADMIN_ROLE, $admin->fresh()->name);
    }
}
