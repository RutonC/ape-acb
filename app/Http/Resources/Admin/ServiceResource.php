<?php

namespace App\Http\Resources\Admin;

use App\Models\Service;
use App\Support\Admin\Translations;
use App\Support\TranslationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serviço no painel (todas as traduções, para o formulário).
 *
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = config('site.default_locale');

        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'slug' => $this->slug,
            'name' => $this->getTranslation('title', $locale),
            'summary_text' => $this->getTranslation('summary', $locale, false),
            ...Translations::present($this->resource),
            'icon' => $this->icon,
            'image_id' => $this->image_id,
            'external_url' => $this->external_url,
            'external_label' => $this->external_label,
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'url' => $this->url(),
            'translation' => TranslationStatus::forApi($this->resource),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
