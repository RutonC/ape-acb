<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class Localization
{
    public static function defaultLocale(): string
    {
        return config('site.default_locale');
    }

    /**
     * Nome da rota no idioma indicado (o predefinido não tem prefixo).
     */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === static::defaultLocale() ? $name : "{$locale}.{$name}";
    }

    /**
     * Remove o prefixo de idioma de um nome de rota ("en.contact" -> "contact").
     */
    public static function baseRouteName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        foreach (array_keys(config('site.locales')) as $locale) {
            if (str_starts_with($name, "{$locale}.")) {
                return substr($name, strlen($locale) + 1);
            }
        }

        return $name;
    }

    /**
     * Caminho de uma rota fixa no idioma indicado: traduz os segmentos
     * estáticos ("/sobre-nos" -> "/about-us"), mantendo os parâmetros ({slug}).
     */
    public static function path(string $path, string $locale): string
    {
        $map = config("site.paths.{$locale}", []);

        return collect(explode('/', $path))
            ->map(fn (string $segment) => $map[$segment] ?? $segment)
            ->implode('/');
    }

    public static function route(string $name, array $parameters = [], ?string $locale = null): string
    {
        return route(static::routeName($name, $locale), $parameters);
    }

    /**
     * URL da página actual noutro idioma (para o comutador PT/EN).
     */
    public static function switchUrl(string $locale): string
    {
        $current = Route::current();
        $base = static::baseRouteName($current?->getName());

        if ($base === null || ! Route::has(static::routeName($base, $locale))) {
            return static::route('home', [], $locale);
        }

        return static::route($base, $current->parameters(), $locale);
    }
}
