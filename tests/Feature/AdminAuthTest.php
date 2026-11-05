<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O Sanctum só usa a sessão em pedidos vindos do próprio site.
        $this->withHeader('Referer', 'http://localhost');
    }

    private function withTwoFactor(User $user): string
    {
        $secret = app(Google2FA::class)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['codigo-um-1234', 'codigo-dois-5678'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    public function test_active_user_with_a_role_signs_in(): void
    {
        $user = User::factory()->admin()->create(['email' => 'ana@acb.co.mz']);

        $this->postJson('/api/admin/auth/login', ['email' => 'ANA@acb.co.mz', 'password' => 'password'])
            ->assertOk()
            ->assertJson(['two_factor' => false]);

        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/admin/me')->assertOk()->assertJsonPath('data.email', 'ana@acb.co.mz');
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->admin()->create(['email' => 'ana@acb.co.mz']);

        $this->postJson('/api/admin/auth/login', ['email' => 'ana@acb.co.mz', 'password' => 'errada'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_and_users_without_a_role_cannot_sign_in(): void
    {
        User::factory()->admin()->inactive()->create(['email' => 'inactivo@acb.co.mz']);
        User::factory()->create(['email' => 'semperfil@acb.co.mz']);

        foreach (['inactivo@acb.co.mz', 'semperfil@acb.co.mz'] as $email) {
            $this->postJson('/api/admin/auth/login', ['email' => $email, 'password' => 'password'])
                ->assertUnprocessable()
                ->assertJsonPath('errors.email.0', 'Esta conta não tem acesso ao painel. Fale com um administrador.');
        }

        $this->assertGuest();
    }

    public function test_user_deactivated_during_the_session_is_signed_out(): void
    {
        $user = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($user)->getJson('/api/admin/me')->assertOk();

        $user->update(['is_active' => false]);

        $this->getJson('/api/admin/me')->assertForbidden();
        $this->assertGuest('web');
    }

    public function test_two_factor_challenge_with_code_or_single_use_recovery_code(): void
    {
        $user = User::factory()->admin()->create(['email' => 'ana@acb.co.mz']);
        $secret = $this->withTwoFactor($user);

        $this->postJson('/api/admin/auth/login', ['email' => 'ana@acb.co.mz', 'password' => 'password'])
            ->assertOk()
            ->assertJson(['two_factor' => true]);
        $this->assertGuest();

        $this->postJson('/api/admin/auth/two-factor-challenge', ['code' => '000000'])->assertUnprocessable();
        $this->postJson('/api/admin/auth/two-factor-challenge', ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertNoContent();
        $this->assertAuthenticatedAs($user);

        // Código de recuperação: serve uma vez.
        $this->postJson('/api/admin/auth/logout')->assertNoContent();
        $this->postJson('/api/admin/auth/login', ['email' => 'ana@acb.co.mz', 'password' => 'password']);
        $this->postJson('/api/admin/auth/two-factor-challenge', ['recovery_code' => 'codigo-um-1234'])->assertNoContent();
        $this->assertNotContains('codigo-um-1234', $user->fresh()->recoveryCodes());
    }

    public function test_required_two_factor_blocks_the_panel_until_configured(): void
    {
        config(['site.admin_mfa_required' => true]);
        $user = User::factory()->admin()->create(['email' => 'ana@acb.co.mz']);

        $this->postJson('/api/admin/auth/login', ['email' => 'ana@acb.co.mz', 'password' => 'password'])->assertOk();

        $this->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('data.two_factor.required', true)
            ->assertJsonPath('data.two_factor.enabled', false);
        $this->getJson('/api/admin/users')->assertForbidden()->assertJsonPath('code', 'two_factor_required');

        // Acabou de entrar: activa sem confirmar a palavra-passe de novo.
        $this->postJson('/api/admin/auth/user/two-factor-authentication')->assertOk();
        $secret = decrypt($user->fresh()->two_factor_secret);
        $this->getJson('/api/admin/auth/user/two-factor-qr-code')->assertOk()->assertJsonStructure(['svg', 'url']);

        $this->postJson('/api/admin/auth/user/confirmed-two-factor-authentication', ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertOk();

        $this->getJson('/api/admin/auth/user/two-factor-recovery-codes')->assertOk()->assertJsonCount(8);
        $this->getJson('/api/admin/users')->assertOk();
    }

    public function test_two_factor_secrets_are_encrypted_at_rest(): void
    {
        $user = User::factory()->admin()->create();
        $secret = $this->withTwoFactor($user);

        $raw = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame($secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString('codigo-um-1234', $raw->two_factor_recovery_codes);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
    }

    public function test_password_reset_link_opens_the_panel_form(): void
    {
        Notification::fake();
        $user = User::factory()->admin()->create(['email' => 'ana@acb.co.mz']);

        $this->postJson('/api/admin/auth/forgot-password', ['email' => 'ana@acb.co.mz'])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, &$token) {
            $token = $notification->token;

            return str_contains($notification->toMail($user)->actionUrl, '/admin/repor-palavra-passe?token=');
        });

        $this->postJson('/api/admin/auth/reset-password', [
            'token' => $token,
            'email' => 'ana@acb.co.mz',
            'password' => 'Nova-Palavra-2026',
            'password_confirmation' => 'Nova-Palavra-2026',
        ])->assertOk();

        $this->assertTrue(Hash::check('Nova-Palavra-2026', $user->fresh()->password));
    }

    public function test_user_updates_own_profile_and_password(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->putJson('/api/admin/auth/user/profile-information', ['name' => 'Ana Mendes', 'email' => 'ana.mendes@acb.co.mz'])
            ->assertOk();
        $this->assertSame('Ana Mendes', $user->fresh()->name);

        $this->putJson('/api/admin/auth/user/password', [
            'current_password' => 'errada',
            'password' => 'Nova-Palavra-2026',
            'password_confirmation' => 'Nova-Palavra-2026',
        ])->assertUnprocessable();

        $this->putJson('/api/admin/auth/user/password', [
            'current_password' => 'password',
            'password' => 'Nova-Palavra-2026',
            'password_confirmation' => 'Nova-Palavra-2026',
        ])->assertOk();
        $this->assertTrue(Hash::check('Nova-Palavra-2026', $user->fresh()->password));
    }

    public function test_admin_seeder_is_idempotent(): void
    {
        config(['site.admin.email' => 'admin@acb.co.mz', 'site.admin.password' => 'secret-password']);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@acb.co.mz')->count());
    }
}
