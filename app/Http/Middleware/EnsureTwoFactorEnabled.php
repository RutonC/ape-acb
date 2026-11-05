<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Com a verificação em dois passos obrigatória (ADMIN_MFA_REQUIRED), o painel
 * só fica disponível depois de a configurar.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('site.admin_mfa_required') && ! $request->user()?->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'Configure a verificação em dois passos para continuar.',
                'code' => 'two_factor_required',
            ], 403);
        }

        return $next($request);
    }
}
