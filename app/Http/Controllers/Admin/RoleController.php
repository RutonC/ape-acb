<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Perfis e as áreas do painel a que cada um dá acesso. O perfil
 * Administrador tem sempre acesso a tudo e não pode ser alterado.
 */
class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', 'web')->with('permissions')->orderBy('id')->get();

        // withCount('users') não funciona no Role do Spatie (a relação depende do guard do registo).
        $counts = DB::table(config('permission.table_names.model_has_roles'))
            ->selectRaw('role_id, count(*) as total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        return response()->json(['data' => $roles->map(fn (Role $role) => $this->present($role, (int) ($counts[$role->id] ?? 0)))->values()]);
    }

    /**
     * Áreas do painel, agrupadas, para o formulário de perfis.
     */
    public function permissions(): JsonResponse
    {
        $groups = collect(Permissions::grouped())
            ->map(fn (array $items, string $group) => [
                'group' => $group,
                'items' => collect($items)->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values(),
            ])
            ->values();

        return response()->json(['data' => $groups]);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(['data' => $this->present($role->load('permissions'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json(['data' => $this->present($role->load('permissions'))], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($this->isAdministrator($role)) {
            return response()->json(['message' => 'O perfil Administrador tem sempre acesso a tudo e não pode ser alterado.'], 422);
        }

        $data = $this->validated($request, $role);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json(['data' => $this->present($role->load('permissions'))]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($this->isAdministrator($role)) {
            return response()->json(['message' => 'O perfil Administrador não pode ser apagado.'], 422);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'Só é possível apagar perfis sem utilizadores.'], 422);
        }

        $role->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::ALL))],
        ], attributes: ['name' => 'nome do perfil', 'permissions' => 'áreas']);
    }

    private function isAdministrator(Role $role): bool
    {
        return $role->name === Permissions::ADMIN_ROLE;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Role $role, ?int $usersCount = null): array
    {
        $isAdmin = $this->isAdministrator($role);

        return [
            'id' => $role->id,
            'name' => $role->name,
            'is_admin' => $isAdmin,
            'permissions' => $isAdmin ? array_keys(Permissions::ALL) : $role->permissions->pluck('name')->values(),
            'users_count' => $usersCount ?? $role->users()->count(),
        ];
    }
}
