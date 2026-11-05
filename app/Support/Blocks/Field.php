<?php

namespace App\Support\Blocks;

/**
 * Definições de campos dos blocos, num formato neutro (array) que o painel
 * React desenha e o servidor usa para validar e limpar os dados.
 *
 * Tipos: text, textarea, richtext, number, media, icon, select, buttons
 * (escolha em botões), toggle, link, button (texto + ligação), list (lista de
 * textos), repeater (lista de itens com campos), group (agrupamento visual),
 * note (texto informativo).
 */
class Field
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function make(string $type, string $name, string $label, array $extra = []): array
    {
        return ['type' => $type, 'name' => $name, 'label' => $label, ...$extra];
    }

    /** @return array<string, mixed> */
    public static function text(string $name, string $label, ?int $max = 255, bool $required = false, array $extra = []): array
    {
        return static::make('text', $name, $label, ['max' => $max, 'required' => $required, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function textarea(string $name, string $label, bool $required = false, int $rows = 3, array $extra = []): array
    {
        return static::make('textarea', $name, $label, ['max' => 5000, 'required' => $required, 'rows' => $rows, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function richtext(string $name, string $label, bool $required = false, array $extra = []): array
    {
        return static::make('richtext', $name, $label, ['max' => 200000, 'required' => $required, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function number(string $name, string $label, bool $required = false, array $extra = []): array
    {
        return static::make('number', $name, $label, ['required' => $required, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function media(string $name, string $label, bool $required = false, array $extra = []): array
    {
        return static::make('media', $name, $label, ['required' => $required, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function icon(string $name, string $label = 'Ícone', array $extra = []): array
    {
        return static::make('icon', $name, $label, $extra);
    }

    /**
     * @param  array<int|string, string>|string  $options  opções fixas (valor => rótulo) ou nome de uma lista dinâmica
     * @return array<string, mixed>
     */
    public static function select(string $name, string $label, array|string $options, mixed $default = null, array $extra = []): array
    {
        return static::make('select', $name, $label, ['options' => static::options($options), 'default' => $default, ...$extra]);
    }

    /**
     * @param  array<int|string, string>  $options
     * @return array<string, mixed>
     */
    public static function buttons(string $name, string $label, array $options, mixed $default = null, array $extra = []): array
    {
        return static::make('buttons', $name, $label, ['options' => static::options($options), 'default' => $default, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function toggle(string $name, string $label, bool $default = false, array $extra = []): array
    {
        return static::make('toggle', $name, $label, ['default' => $default, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function link(string $name, string $label = 'Ligação', bool $withNewTab = true, array $extra = []): array
    {
        return static::make('link', $name, $label, ['with_new_tab' => $withNewTab, ...$extra]);
    }

    /**
     * Botão: texto ("{name}.label") e ligação no mesmo objecto, como o site lê.
     *
     * @return array<string, mixed>
     */
    public static function button(string $name, string $label, ?string $defaultLabel = null, bool $withNewTab = true, array $extra = []): array
    {
        return static::make('button', $name, $label, ['default_label' => $defaultLabel, 'with_new_tab' => $withNewTab, ...$extra]);
    }

    /** @return array<string, mixed> */
    public static function list(string $name, string $label, string $addLabel = 'Adicionar', ?int $maxItems = null, array $extra = []): array
    {
        return static::make('list', $name, $label, ['add_label' => $addLabel, 'max_items' => $maxItems, 'max' => 255, ...$extra]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  string|array<int, string>|null  $itemLabel  campo(s) usados como título de cada item
     * @return array<string, mixed>
     */
    public static function repeater(string $name, string $label, array $fields, string|array|null $itemLabel = null, string $addLabel = 'Adicionar', ?int $maxItems = null, array $extra = []): array
    {
        return static::make('repeater', $name, $label, [
            'fields' => $fields,
            'item_label' => $itemLabel === null ? null : (array) $itemLabel,
            'add_label' => $addLabel,
            'max_items' => $maxItems,
            ...$extra,
        ]);
    }

    /**
     * Agrupamento visual (os campos ficam no mesmo nível dos dados).
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function group(string $label, array $fields, array $extra = []): array
    {
        return ['type' => 'group', 'name' => null, 'label' => $label, 'fields' => $fields, ...$extra];
    }

    /** @return array<string, mixed> */
    public static function note(string $text): array
    {
        return ['type' => 'note', 'name' => null, 'label' => $text];
    }

    /**
     * @param  array<int|string, string>|string  $options
     * @return array<int, array{value: int|string, label: string}>|string
     */
    private static function options(array|string $options): array|string
    {
        if (is_string($options)) {
            return $options;
        }

        return array_map(fn ($value, $label) => ['value' => $value, 'label' => $label], array_keys($options), $options);
    }
}
