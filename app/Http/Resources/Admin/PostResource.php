<?php

namespace App\Http\Resources\Admin;

use App\Enums\PageStatus;
use App\Models\Post;
use App\Support\Admin\Translations;
use App\Support\TranslationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Artigo do blog no painel.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = config('site.default_locale');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslation('title', $locale),
            ...Translations::present($this->resource),
            'status' => $this->status->value,
            // Estado como aparece no painel: rascunho, agendado (data futura) ou publicado.
            'state' => match (true) {
                $this->trashed() => 'trashed',
                $this->status === PageStatus::Draft => 'draft',
                $this->published_at?->isFuture() => 'scheduled',
                default => 'published',
            },
            'published_at' => $this->published_at?->toIso8601String(),
            'post_category_id' => $this->post_category_id,
            'category_name' => $this->category?->getTranslation('name', $locale),
            'tags' => $this->tags->pluck('id')->values(),
            'author_id' => $this->author_id,
            'author_name' => $this->authorName(),
            'cover_image_id' => $this->cover_image_id,
            'url' => $this->url(),
            'is_visible' => ! $this->trashed() && $this->isVisible(),
            'translation' => TranslationStatus::forApi($this->resource),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
