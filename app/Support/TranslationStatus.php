<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\JobCategory;
use App\Models\JobOpening;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Estado das traduções: campos preenchidos em português (idioma de
 * referência) que ainda não têm versão no outro idioma.
 */
class TranslationStatus
{
    /** Modelos com conteúdo traduzível, pela ordem em que aparecem no painel. */
    public const MODELS = [
        Page::class,
        Service::class,
        JobOpening::class,
        JobCategory::class,
        Post::class,
        PostCategory::class,
        PostTag::class,
        TeamMember::class,
    ];

    private const LABELS = [
        'title' => 'Título',
        'name' => 'Nome',
        'content' => 'Conteúdo',
        'excerpt' => 'Resumo',
        'summary' => 'Resumo',
        'tagline' => 'Frase de destaque',
        'intro' => 'Introdução',
        'details' => 'Detalhes',
        'audience' => 'Público-alvo',
        'timeline' => 'Prazo',
        'client' => 'Cliente',
        'challenge' => 'Desafio',
        'solution' => 'Solução',
        'results' => 'Resultados',
        'duration' => 'Duração',
        'contract_type' => 'Tipo de contrato',
        'location' => 'Local',
        'description' => 'Descrição',
        'requirements' => 'Requisitos',
        'role' => 'Função',
        'bio' => 'Biografia',
        'meta_title' => 'Título SEO',
        'meta_description' => 'Descrição SEO',
    ];

    /**
     * Campos com texto em português e sem versão no idioma indicado.
     *
     * @return array<int, string>
     */
    public static function missingFields(Model $record, string $locale = 'en'): array
    {
        $source = Localization::defaultLocale();

        return array_values(array_filter(
            $record->getTranslatableAttributes(),
            fn (string $field) => filled($record->getTranslation($field, $source, false))
                && blank($record->getTranslation($field, $locale, false)),
        ));
    }

    public static function isComplete(Model $record, string $locale = 'en'): bool
    {
        return static::missingFields($record, $locale) === [];
    }

    /**
     * @param  array<int, string>  $fields
     */
    public static function labels(array $fields): string
    {
        return collect($fields)->map(fn (string $field) => static::LABELS[$field] ?? $field)->implode(', ');
    }

    /**
     * Estado para a API do painel (coluna "EN" das tabelas).
     *
     * @return array{complete: bool, missing: array<int, string>}
     */
    public static function forApi(Model $record, string $locale = 'en'): array
    {
        $missing = static::missingFields($record, $locale);

        return [
            'complete' => $missing === [],
            'missing' => array_map(fn (string $field) => static::LABELS[$field] ?? $field, $missing),
        ];
    }

    /**
     * Todos os registos com traduções em falta.
     *
     * @return Collection<int, array{record: Model, missing: array<int, string>}>
     */
    public static function pending(string $locale = 'en'): Collection
    {
        return collect(static::MODELS)
            ->flatMap(fn (string $model) => $model::query()->get())
            ->map(fn (Model $record) => ['record' => $record, 'missing' => static::missingFields($record, $locale)])
            ->filter(fn (array $item) => $item['missing'] !== [])
            ->values();
    }
}
