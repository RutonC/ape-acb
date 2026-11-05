<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Criar ou editar um utilizador do painel. Ao editar, a palavra-passe em
 * branco mantém a actual.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique(User::class, 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::defaults(), 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function ($validator): void {
            /** @var User|null $user */
            $user = $this->route('user');

            if (! $user) {
                return;
            }

            if ($user->isLastAdministrator()) {
                if (! in_array(User::administratorRole()->id, array_map('intval', (array) $this->input('roles')), true)) {
                    $validator->errors()->add('roles', 'É o último administrador activo: mantenha o perfil Administrador.');
                }

                if ($this->has('is_active') && ! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'É o último administrador activo: não pode ser desactivado.');
                }
            }

            if ($user->is($this->user()) && $this->has('is_active') && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Não pode desactivar a sua própria conta.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'palavra-passe',
            'roles' => 'perfis',
            'is_active' => 'estado',
        ];
    }
}
