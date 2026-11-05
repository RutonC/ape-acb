<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Links;
use Illuminate\Http\JsonResponse;

/**
 * Opções do campo de ligação (menus e botões dos blocos): páginas fixas
 * do site, serviços e páginas criadas no painel.
 */
class LinkOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locale = config('site.default_locale');

        return response()->json([
            'routes' => collect(Links::routeOptions())->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])->values(),
            'pages' => Page::query()->orderBy('slug')->get()->map(fn (Page $page) => [
                'value' => $page->id,
                'label' => $page->getTranslation('title', $locale).' (/'.$page->slug.')',
            ])->values(),
        ]);
    }
}
