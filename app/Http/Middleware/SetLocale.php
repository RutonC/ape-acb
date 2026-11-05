<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Define o idioma a partir do grupo de rotas (pt sem prefixo, en com /en).
     */
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale = array_key_exists((string) $locale, config('site.locales'))
            ? $locale
            : config('site.default_locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
