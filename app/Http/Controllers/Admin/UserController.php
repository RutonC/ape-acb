<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Support\Admin\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Utilizadores do painel (Administração > Utilizadores).
 */
class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = ListQuery::for(User::query()->with('roles'), $request)
            ->search(['name', 'email'])
            ->filter('role', fn ($query, string $role) => $query->whereHas('roles', fn ($q) => $q->whereKey((int) $role)))
            ->filter('active', fn ($query, string $active) => $query->where('is_active', $active === '1'))
            ->sort(['name', 'email', 'created_at'], 'name')
            ->paginate();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $user->syncRoles($request->validated('roles'));

        return (new UserResource($user->load('roles')))->response()->setStatusCode(201);
    }

    public function update(UserRequest $request, User $user): UserResource
    {
        $data = $request->safe()->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);
        $user->syncRoles($request->validated('roles'));

        return new UserResource($user->load('roles'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'Não pode apagar a sua própria conta.'], 422);
        }

        if ($user->isLastAdministrator()) {
            return response()->json(['message' => 'É o último administrador activo e não pode ser apagado.'], 422);
        }

        $user->delete();

        return response()->json(status: 204);
    }

    /**
     * Repor a verificação em dois passos (ex.: o utilizador perdeu o telemóvel).
     */
    public function resetTwoFactor(User $user): UserResource
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return new UserResource($user->load('roles'));
    }
}
