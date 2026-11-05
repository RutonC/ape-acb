<?php

namespace App\Support;

use App\Enums\MenuLocation;
use App\Enums\ServiceKind;
use App\Models\Menu;
use App\Models\Service;

/**
 * Menus do site: lê os menus do painel e, se ainda não existirem, usa a
 * estrutura inicial de config/navigation.php.
 */
class Navigation
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $resolved = [];

    /**
     * Itens prontos a desenhar no idioma actual.
     *
     * @return array<int, array{label: string, url: string, new_tab: bool, active: bool, children: array<int, array<string, mixed>>}>
     */
    public function items(MenuLocation|string $location): array
    {
        $location = $location instanceof MenuLocation ? $location : MenuLocation::from($location);
        $key = $location->value.'.'.app()->getLocale();

        return $this->resolved[$key] ??= $this->resolve($this->storedItems($location));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function storedItems(MenuLocation $location): array
    {
        return Menu::query()->where('location', $location)->first()?->items
            ?? static::defaultItems($location);
    }

    /**
     * Estrutura inicial (usada pelo seeder e enquanto o menu não é editado).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultItems(MenuLocation $location): array
    {
        $source = match ($location) {
            MenuLocation::Header => config('navigation.main'),
            MenuLocation::Footer => config('navigation.footer'),
            MenuLocation::Legal => config('navigation.legal'),
            MenuLocation::FooterServices => [],
        };

        if ($location === MenuLocation::FooterServices) {
            return static::catalogItems(ServiceKind::Service);
        }

        return array_map(fn (array $item) => static::fromConfig($item), $source);
    }

    /**
     * Itens de menu para os serviços publicados.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function catalogItems(ServiceKind $kind): array
    {
        return Service::published()->ofKind($kind)->get()
            ->map(fn (Service $service) => [
                'label' => $service->getTranslations('title'),
                'link' => ['type' => 'route', 'route' => $service->linkKey()],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function fromConfig(array $item): array
    {
        $link = isset($item['url'])
            ? ['type' => 'url', 'url' => $item['url'], 'new_tab' => true]
            : ['type' => 'route', 'route' => $item['route'].(isset($item['params']['slug']) ? '|'.$item['params']['slug'] : '')];

        $children = [
            ...(isset($item['children_from']) ? static::catalogItems(ServiceKind::from($item['children_from'])) : []),
            ...array_map(fn (array $child) => static::fromConfig($child), $item['children'] ?? []),
        ];

        return array_filter([
            'label' => [
                'pt' => $item['label'],
                'en' => trans($item['label'], locale: 'en'),
            ],
            'link' => $link,
            'children' => $children ?: null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function resolve(array $items): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $url = Links::url($item['link'] ?? null);
            $label = localized($item['label'] ?? null);

            if ($url === null || $label === '') {
                continue;
            }

            $children = $this->resolve($item['children'] ?? []);

            $resolved[] = [
                'label' => $label,
                'url' => $url,
                'new_tab' => Links::opensInNewTab($item['link'] ?? null),
                'active' => $this->isCurrent($url) || in_array(true, array_column($children, 'active'), true),
                'children' => $children,
            ];
        }

        return $resolved;
    }

    private function isCurrent(string $url): bool
    {
        $itemPath = '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');
        $currentPath = '/'.trim(request()->path(), '/');

        if (parse_url($url, PHP_URL_HOST) !== null && parse_url($url, PHP_URL_HOST) !== request()->getHost()) {
            return false;
        }

        if ($itemPath === $currentPath) {
            return true;
        }

        $homes = array_map(fn (string $locale) => '/'.trim(parse_url(lroute('home', locale: $locale), PHP_URL_PATH) ?? '', '/'), array_keys(config('site.locales')));

        return ! in_array($itemPath, $homes, true) && str_starts_with($currentPath, $itemPath.'/');
    }
}
