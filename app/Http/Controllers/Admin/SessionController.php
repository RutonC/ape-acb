<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Utilizador com sessão iniciada no painel: dados, áreas a que acede e
 * estado da verificação em dois passos. O login e o logout são do Fortify.
 */
class SessionController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
                'is_admin' => $user->isAdministrator(),
                'areas' => $user->adminAreas(),
                'two_factor' => [
                    'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                    'pending' => filled($user->two_factor_secret) && ! $user->two_factor_confirmed_at,
                    'required' => (bool) config('site.admin_mfa_required'),
                ],
            ],
        ]);
    }
}
