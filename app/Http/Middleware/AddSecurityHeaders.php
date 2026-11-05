<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalho que a imagem de produção não define. X-Frame-Options, nosniff,
 * Referrer-Policy e HSTS são enviados pelo Nginx da imagem (repeti-los aqui
 * duplicava-os na resposta).
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
