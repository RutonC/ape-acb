<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Entra no painel quem está activo e tem pelo menos um perfil.
     */
    public function canAccessAdmin(): bool
    {
        return $this->is_active && $this->roles()->exists();
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(Permissions::ADMIN_ROLE);
    }

    /**
     * Áreas do painel a que o utilizador acede (o Administrador acede a todas).
     *
     * @return array<int, string>
     */
    public function adminAreas(): array
    {
        if ($this->isAdministrator()) {
            return array_keys(Permissions::ALL);
        }

        return array_values(array_intersect(array_keys(Permissions::ALL), $this->getAllPermissions()->pluck('name')->all()));
    }

    /**
     * Último administrador activo: não pode ser apagado, desactivado nem perder o perfil.
     */
    public function isLastAdministrator(): bool
    {
        if (! $this->is_active || ! $this->isAdministrator()) {
            return false;
        }

        return static::role(Permissions::ADMIN_ROLE)->where('is_active', true)->count() === 1;
    }

    /**
     * O perfil Administrador (usado nas validações de perfis).
     */
    public static function administratorRole(): Role
    {
        return Role::findByName(Permissions::ADMIN_ROLE, 'web');
    }
}
