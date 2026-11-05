<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MenuLocation;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Support\Admin\Translations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menus do site (um por localização: cabeçalho, rodapé...). Só o menu do
 * cabeçalho aceita um nível de submenu.
 */
class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        $menus = Menu::query()->get()->sortBy(fn (Menu $menu) => array_search($menu->location, MenuLocation::cases(), true))->values();

        return response()->json(['data' => $menus->map(fn (Menu $menu) => $this->present($menu))]);
    }

    public function show(Menu $menu): JsonResponse
    {
        return response()->json(['data' => $this->present($menu)]);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $locale = config('site.default_locale');
        $item = fn (string $prefix) => [
            "{$prefix}.label" => ['required', 'array'],
            "{$prefix}.label.{$locale}" => ['required', 'string', 'max:60'],
            ...collect(Translations::locales())->reject(fn ($l) => $l === $locale)->mapWithKeys(fn ($l) => ["{$prefix}.label.{$l}" => ['nullable', 'string', 'max:60']])->all(),
            "{$prefix}.link" => ['required', 'array'],
            "{$prefix}.link.type" => ['required', Rule::in(['route', 'page', 'url'])],
            "{$prefix}.link.route" => ['nullable', 'string', 'max:120'],
            "{$prefix}.link.page_id" => ['nullable', 'integer', Rule::exists('pages', 'id')],
            "{$prefix}.link.url" => ['nullable', 'string', 'max:500'],
            "{$prefix}.link.new_tab" => ['boolean'],
        ];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'items' => ['present', 'array', 'max:30'],
            ...$item('items.*'),
            'items.*.children' => ['nullable', 'array', 'max:20'],
            ...$item('items.*.children.*'),
        ], [
            'items.*.label.'.$locale.'.required' => 'Indique o texto do item em português.',
            'items.*.children.*.label.'.$locale.'.required' => 'Indique o texto do item em português.',
        ], ['name' => 'nome']);

        $clean = fn (array $entry) => [
            'label' => array_filter($entry['label'], fn ($value) => filled($value)),
            'link' => [
                'type' => $entry['link']['type'],
                'route' => $entry['link']['type'] === 'route' ? ($entry['link']['route'] ?? null) : null,
                'page_id' => $entry['link']['type'] === 'page' ? ($entry['link']['page_id'] ?? null) : null,
                'url' => $entry['link']['type'] === 'url' ? ($entry['link']['url'] ?? null) : null,
                'new_tab' => (bool) ($entry['link']['new_tab'] ?? false),
            ],
        ];

        $menu->update([
            'name' => $data['name'],
            'items' => array_values(array_map(fn (array $entry) => [
                ...$clean($entry),
                // Submenus só no cabeçalho.
                'children' => $menu->location->allowsChildren() ? array_values(array_map($clean, $entry['children'] ?? [])) : [],
            ], $data['items'])),
        ]);

        return response()->json(['data' => $this->present($menu)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'location' => $menu->location->value,
            'location_label' => $menu->location->getLabel(),
            'description' => $menu->location->getDescription(),
            'allows_children' => $menu->location->allowsChildren(),
            'name' => $menu->name,
            'items' => array_values($menu->items ?? []),
            'items_count' => count($menu->items ?? []),
            'updated_at' => $menu->updated_at?->toIso8601String(),
        ];
    }
}
