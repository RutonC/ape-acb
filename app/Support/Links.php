<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Ligações configuráveis no painel (menus, botões dos blocos).
 *
 * Formato guardado: ['type' => 'route'|'page'|'url', 'route' => 'services.show|dominios',
 * 'page_id' => 3, 'url' => 'https://...', 'new_tab' => false]. As rotas internas
 * são resolvidas no idioma actual, por isso a mesma ligação serve PT e EN.
 */
class Links
{
    /**
     * Páginas fixas do site disponíveis para ligação.
     *
     * @return array<string, string>
     */
    public static function routeOptions(): array
    {
        $options = [
            'home' => 'Início',
            'jobs' => 'Vagas',
            'apply' => 'Candidate-se já',
            'companies' => 'Inscrever minha empresa',
            'services' => 'Serviços',
            'partners' => 'Parceiros',
            'about' => 'Sobre Nós',
            'blog' => 'Notícias',
            'contact' => 'Contactos',
            'privacy' => 'Política de Privacidade',
            'terms' => 'Termos de Utilização',
        ];

        foreach (static::catalog() as $service) {
            $label = $service->kind->getLabel().': '.$service->getTranslation('title', config('site.default_locale'));
            $options[$service->linkKey()] = $service->is_published ? $label : "{$label} (oculto)";
        }

        return $options;
    }

    /**
     * Serviços (memorizados por pedido; limpos quando o catálogo muda).
     *
     * @return Collection<int, Service>
     */
    public static function catalog(): Collection
    {
        return once(fn () => Service::query()->orderBy('kind')->orderBy('sort_order')->orderBy('id')->get());
    }

    /**
     * @param  array<string, mixed>|null  $link
     */
    public static function url(?array $link): ?string
    {
        if (empty($link)) {
            return null;
        }

        return match ($link['type'] ?? 'url') {
            'route' => static::routeUrl($link['route'] ?? null),
            'page' => static::pageUrl($link['page_id'] ?? null),
            default => filled($link['url'] ?? null) ? $link['url'] : null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $link
     */
    public static function opensInNewTab(?array $link): bool
    {
        return (bool) ($link['new_tab'] ?? false);
    }

    private static function routeUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        [$name, $slug] = array_pad(explode('|', $value, 2), 2, null);

        if (! array_key_exists($value, static::routeOptions())) {
            return null;
        }

        // Serviços ocultos não geram ligação (a página devolve 404).
        if ($slug !== null && ! static::catalog()->contains(fn (Service $service) => $service->linkKey() === $value && $service->is_published)) {
            return null;
        }

        return lroute($name, $slug ? ['slug' => $slug] : []);
    }

    private static function pageUrl(mixed $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        return Page::published()->find($id)?->url();
    }
}
