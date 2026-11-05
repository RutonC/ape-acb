<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * sitemap.xml (com as versões PT/EN de cada endereço) e robots.txt.
 */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $pages = Page::published()->get()->keyBy('slug');
        $entries = [];

        // Início e rotas fixas; a data vem da página do painel associada, se existir.
        $entries[] = $this->entry(fn (string $locale) => lroute('home', locale: $locale), $pages->firstWhere('is_homepage', true)?->updated_at, 1.0);

        foreach (Page::ROUTE_PAGES as $slug => $route) {
            $entries[] = $this->entry(fn (string $locale) => lroute($route, locale: $locale), $pages->get($slug)?->updated_at, 0.8);
        }

        // Páginas livres criadas no painel.
        $pages
            ->reject(fn (Page $page) => $page->is_homepage || isset(Page::ROUTE_PAGES[$page->slug]))
            ->each(function (Page $page) use (&$entries) {
                $entries[] = $this->entry(fn (string $locale) => $page->url($locale), $page->updated_at, 0.6);
            });

        $items = [
            ...Service::published()->orderBy('sort_order')->get(),
            ...JobOpening::query()->orderByDesc('id')->get(),
            ...Post::published()->latest('published_at')->get(),
            ...PostCategory::whereHas('posts', fn ($query) => $query->published())->get(),
        ];

        foreach ($items as $item) {
            $entries[] = $this->entry(fn (string $locale) => $item->url($locale), $item->updated_at, $item instanceof PostCategory ? 0.4 : 0.7);
        }

        return response()
            ->view('seo.sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /pre-visualizar', '', 'Sitemap: '.route('sitemap')]
            // Ambientes de teste/desenvolvimento nunca devem ser indexados.
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param  callable(string): string  $url
     * @return array{urls: array<string, string>, lastmod: ?Carbon, priority: float}
     */
    private function entry(callable $url, ?Carbon $lastmod, float $priority): array
    {
        $urls = [];

        foreach (array_keys(config('site.locales')) as $locale) {
            $urls[$locale] = $url($locale);
        }

        return ['urls' => $urls, 'lastmod' => $lastmod, 'priority' => $priority];
    }
}
