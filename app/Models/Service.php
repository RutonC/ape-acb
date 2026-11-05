<?php

namespace App\Models;

use App\Enums\ServiceKind;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Once;
use Spatie\Translatable\HasTranslations;

/**
 * Serviço do catálogo.
 *
 * "details" guarda, por idioma, as secções da página de detalhe:
 * [['title' => 'O que inclui', 'icon' => 'check-badge', 'content' => '<p>…</p>'], …].
 *
 * @property ServiceKind $kind
 */
#[Fillable([
    'kind', 'slug', 'title', 'tagline', 'summary', 'intro', 'details', 'audience', 'timeline',
    'icon', 'image_id', 'external_url', 'external_label', 'sort_order', 'is_published', 'meta_title', 'meta_description',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['title', 'tagline', 'summary', 'intro', 'details', 'audience', 'timeline', 'meta_title', 'meta_description'];

    protected static function booted(): void
    {
        // O catálogo é memorizado em App\Support\Links::catalog().
        static::saved(fn () => Once::flush());
        static::deleted(fn () => Once::flush());
    }

    protected function casts(): array
    {
        return [
            'kind' => ServiceKind::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopeOfKind(Builder $query, ServiceKind $kind): void
    {
        $query->where('kind', $kind)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /**
     * Secções da página de detalhe no idioma actual (com recurso ao predefinido).
     *
     * @return array<int, array{title?: string, icon?: string, content?: string}>
     */
    public function detailSections(): array
    {
        return $this->getTranslation('details', app()->getLocale(), useFallbackLocale: false)
            ?: $this->getTranslation('details', config('site.default_locale'), useFallbackLocale: false)
            ?: [];
    }

    public function url(?string $locale = null): string
    {
        return lroute($this->kind->showRoute(), ['slug' => $this->slug], $locale);
    }

    /**
     * Valor da opção de ligação usada nos menus e botões (ver App\Support\Links).
     */
    public function linkKey(): string
    {
        return $this->kind->showRoute().'|'.$this->slug;
    }
}
