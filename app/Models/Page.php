<?php

namespace App\Models;

use App\Enums\PageStatus;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Spatie\Translatable\HasTranslations;

/**
 * Página gerida no painel, composta por blocos (content) em cada idioma.
 *
 * @property array<int, array{type: string, data: array<string, mixed>}>|null $content
 */
#[Fillable(['title', 'slug', 'content', 'status', 'is_homepage', 'meta_title', 'meta_description', 'og_image_id', 'published_at'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * Caminhos usados pelo sistema que não podem ser endereços de páginas.
     *
     * @var array<int, string>
     */
    public const RESERVED_SLUGS = ['admin', 'api', 'en', 'pt', 'img', 'storage', 'build', 'up', 'pre-visualizar', 'sanctum'];

    /**
     * Páginas do painel que dão conteúdo (blocos e metadados) a uma rota fixa
     * do site: o endereço público é o dessa rota, não /{slug}.
     */
    public const ROUTE_PAGES = [
        'sobre-nos' => 'about',
        'vagas' => 'jobs',
        'candidatura' => 'apply',
        'empresas' => 'companies',
        'parceiros' => 'partners',
        'servicos' => 'services',
        'noticias' => 'blog',
        'contactos' => 'contact',
        'politica-de-privacidade' => 'privacy',
        'termos-de-utilizacao' => 'terms',
    ];

    /** @var array<int, string> */
    public array $translatable = ['title', 'content', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'is_homepage' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->status === PageStatus::Published && $page->published_at === null) {
                $page->published_at = now();
            }
        });

        // Só pode existir uma página inicial.
        static::saved(function (Page $page) {
            if ($page->is_homepage) {
                static::query()->whereKeyNot($page->getKey())->where('is_homepage', true)->update(['is_homepage' => false]);
            }
        });
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    /**
     * Blocos activos do idioma actual (com recurso ao idioma predefinido).
     *
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function blocks(): array
    {
        $blocks = $this->getTranslation('content', app()->getLocale(), useFallbackLocale: false)
            ?: $this->getTranslation('content', config('site.default_locale'), useFallbackLocale: false)
            ?: [];

        return array_values(array_filter(
            $blocks,
            fn (array $block) => ($block['data']['is_active'] ?? true) !== false,
        ));
    }

    public function url(?string $locale = null): string
    {
        return match (true) {
            $this->is_homepage => lroute('home', locale: $locale),
            isset(static::ROUTE_PAGES[$this->slug]) => lroute(static::ROUTE_PAGES[$this->slug], locale: $locale),
            default => lroute('page', ['slug' => $this->slug], $locale),
        };
    }

    /**
     * Endereço assinado (válido 2 horas) para ver a página mesmo em rascunho.
     */
    public function previewUrl(?string $locale = null): string
    {
        return URL::temporarySignedRoute('pages.preview', now()->addHours(2), [
            'page' => $this->getKey(),
            'locale' => $locale ?? config('site.default_locale'),
        ]);
    }
}
