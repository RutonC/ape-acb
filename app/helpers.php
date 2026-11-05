<?php

use App\Support\Localization;

if (! function_exists('lroute')) {
    /**
     * route() no idioma actual: lroute('contact') -> /contactos ou /en/contact.
     */
    function lroute(string $name, array $parameters = [], ?string $locale = null): string
    {
        return Localization::route($name, $parameters, $locale);
    }
}

if (! function_exists('long_date')) {
    /**
     * Data por extenso. Em português, com o mês em maiúscula: 12 de Dezembro de 2019.
     */
    function long_date(?DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        $date = Illuminate\Support\Carbon::instance($date);

        return app()->getLocale() === 'pt'
            ? $date->isoFormat('D [de] ').Illuminate\Support\Str::ucfirst($date->isoFormat('MMMM')).$date->isoFormat(' [de] YYYY')
            : $date->isoFormat('LL');
    }
}

if (! function_exists('localized')) {
    /**
     * Valor de um campo multi-idioma (['pt' => ..., 'en' => ...]) no idioma actual,
     * com recurso ao idioma predefinido quando a tradução está vazia.
     *
     * @param  array<string, string|null>|string|null  $values
     */
    function localized(array|string|null $values, ?string $locale = null): string
    {
        if (! is_array($values)) {
            return (string) $values;
        }

        $locale ??= app()->getLocale();

        return (string) (($values[$locale] ?? null) ?: ($values[config('site.default_locale')] ?? '') ?: '');
    }
}

if (! function_exists('youtube_embed_url')) {
    /**
     * Converte um endereço do YouTube (watch, youtu.be, shorts) no URL de incorporação
     * sem cookies de rastreio (youtube-nocookie.com).
     */
    function youtube_embed_url(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        return preg_match($pattern, $url, $matches) ? "https://www.youtube-nocookie.com/embed/{$matches[1]}" : null;
    }
}
