<?php

namespace App\Http\Resources\Admin;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\Admin\Translations;
use App\Support\TranslationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Página do construtor no painel (blocos de cada idioma incluídos).
 *
 * @mixin Page
 */
class PageResource extends JsonResource
{
    /** Rótulos das rotas fixas que uma página pode alimentar. */
    private const ROUTE_LABELS = [
        'about' => 'Sobre Nós', 'jobs' => 'Vagas', 'apply' => 'Candidatura', 'companies' => 'Empresas', 'partners' => 'Parceiros', 'services' => 'Serviços',
        'blog' => 'Notícias', 'contact' => 'Contactos',
        'privacy' => 'Política de Privacidade', 'terms' => 'Termos de Utilização',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $route = Page::ROUTE_PAGES[$this->slug] ?? null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslation('title', config('site.default_locale')),
            ...Translations::present($this->resource),
            'status' => $this->status->value,
            'state' => $this->trashed() ? 'trashed' : ($this->status === PageStatus::Published ? 'published' : 'draft'),
            'is_homepage' => $this->is_homepage,
            // Página que dá conteúdo a uma página fixa do site (ex.: sobre-nos).
            'fixed_route' => $route ? (self::ROUTE_LABELS[$route] ?? $route) : null,
            'blocks_count' => collect(Translations::locales())->mapWithKeys(fn (string $locale) => [$locale => count($this->getTranslation('content', $locale, false) ?: [])]),
            'og_image_id' => $this->og_image_id,
            'url' => $this->url(),
            'is_visible' => ! $this->trashed() && $this->isPublished(),
            'translation' => TranslationStatus::forApi($this->resource),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
