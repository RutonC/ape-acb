<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'roles' => $this->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
            'is_self' => $this->is($request->user()),
            'is_last_admin' => $this->isLastAdministrator(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
