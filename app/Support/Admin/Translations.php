<?php

namespace App\Support\Admin;

use Illuminate\Database\Eloquent\Model;

/**
 * Campos traduzíveis na API do painel: chegam e saem como
 * { "pt": "...", "en": "..." }. Só o português (idioma de referência)
 * pode ser obrigatório; idiomas vazios não são guardados, para o site
 * usar o português como alternativa.
 */
class Translations
{
    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return array_keys(config('site.locales'));
    }

    /**
     * Regras de validação para um campo traduzível.
     *
     * @param  array<int, mixed>  $rules  regras de cada idioma (ex.: ['string', 'max:80'])
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $field, array $rules = ['string'], bool $required = false): array
    {
        $result = [$field => [$required ? 'required' : 'nullable', 'array']];

        foreach (static::locales() as $locale) {
            $isDefault = $locale === config('site.default_locale');
            $result["{$field}.{$locale}"] = [$required && $isDefault ? 'required' : 'nullable', ...$rules];
        }

        return $result;
    }

    /**
     * Nomes legíveis para as mensagens de validação ("título (PT)").
     *
     * @param  array<string, string>  $labels  campo => rótulo
     * @return array<string, string>
     */
    public static function attributes(array $labels): array
    {
        $result = [];

        foreach ($labels as $field => $label) {
            $result[$field] = $label;
            foreach (static::locales() as $locale) {
                $result["{$field}.{$locale}"] = $label.' ('.strtoupper($locale).')';
            }
        }

        return $result;
    }

    /**
     * Valores a gravar: só os idiomas preenchidos.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     * @return array<string, array<string, mixed>>
     */
    public static function values(array $data, array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $result[$field] = array_filter(
                (array) $data[$field],
                fn ($value, $locale) => in_array($locale, static::locales(), true) && static::filled($value),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        return $result;
    }

    /**
     * Preenche os campos traduzíveis do registo (substitui as traduções:
     * um idioma apagado no formulário deixa de existir).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    public static function fill(Model $record, array $data, array $fields): void
    {
        foreach (static::values($data, $fields) as $field => $translations) {
            $record->replaceTranslations($field, $translations);
        }
    }

    /**
     * Todos os idiomas de cada campo, para o formulário de edição.
     *
     * @param  array<int, string>|null  $fields
     * @return array<string, array<string, mixed>>
     */
    public static function present(Model $record, ?array $fields = null): array
    {
        $result = [];

        foreach ($fields ?? $record->getTranslatableAttributes() as $field) {
            $translations = $record->getTranslations($field);
            $result[$field] = array_combine(static::locales(), array_map(fn (string $locale) => $translations[$locale] ?? null, static::locales()));
        }

        return $result;
    }

    private static function filled(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        // Texto do editor sem conteúdo (ex.: "<p></p>") conta como vazio.
        return trim(strip_tags(str_replace('&nbsp;', ' ', (string) $value))) !== '' || str_contains((string) $value, '<img');
    }
}
