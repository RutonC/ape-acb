<?php

namespace App\Support\Blocks;

use App\Models\Media;
use App\Support\IconOptions;
use App\Support\PageBlocks;

/**
 * Valida e limpa os blocos de uma página segundo BlockSchema: só ficam os
 * campos conhecidos, com os tipos certos. Os erros vêm com o caminho do campo
 * (ex.: "content.pt.2.data.items.0.title") para o painel os mostrar no sítio.
 */
class BlockData
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<int, bool> */
    private array $mediaIds;

    /** @var array<int, string> */
    private array $icons;

    public function __construct()
    {
        $this->icons = array_keys(IconOptions::all());
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array{0: array<int, array{type: string, data: array<string, mixed>}>, 1: array<string, string>}
     */
    public static function clean(array $blocks, string $path): array
    {
        $instance = new static;
        $instance->mediaIds = array_fill_keys(Media::query()->pluck('id')->all(), true);
        $definitions = BlockSchema::definitions();
        $result = [];

        foreach (array_values($blocks) as $index => $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            $blockPath = "{$path}.{$index}";

            if (! is_string($type) || ! in_array($type, PageBlocks::TYPES, true) || ! isset($definitions[$type])) {
                $instance->errors["{$blockPath}.type"] = 'Tipo de bloco desconhecido.';

                continue;
            }

            $definition = $definitions[$type];
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            $result[] = [
                'type' => $type,
                'data' => [
                    ...$instance->fields($definition['fields'], $data, "{$blockPath}.data"),
                    ...$instance->appearance($data, $definition, "{$blockPath}.data"),
                ],
            ];
        }

        return [$result, $instance->errors];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $fields, array $data, string $path): array
    {
        $result = [];

        foreach ($fields as $field) {
            if ($field['type'] === 'note') {
                continue;
            }

            if ($field['type'] === 'group') {
                $result = [...$result, ...$this->fields($field['fields'], $data, $path)];

                continue;
            }

            $name = $field['name'];
            $result[$name] = $this->value($field, $data[$name] ?? null, "{$path}.{$name}");
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function value(array $field, mixed $value, string $path): mixed
    {
        $label = $field['label'];

        switch ($field['type']) {
            case 'text':
            case 'textarea':
            case 'richtext':
                $value = is_scalar($value) ? trim((string) $value) : '';
                $empty = $field['type'] === 'richtext'
                    ? trim(strip_tags(str_replace('&nbsp;', ' ', $value))) === '' && ! str_contains($value, '<img')
                    : $value === '';

                if ($empty) {
                    $this->requireIf($field, $path);

                    return null;
                }

                if (! empty($field['max']) && mb_strlen($value) > $field['max']) {
                    $this->errors[$path] = "«{$label}» pode ter no máximo {$field['max']} caracteres.";
                }

                return $value;

            case 'number':
                if ($value === null || $value === '') {
                    $this->requireIf($field, $path);

                    return null;
                }

                if (! is_numeric($value)) {
                    $this->errors[$path] = "«{$label}» tem de ser um número.";

                    return null;
                }

                return $value + 0;

            case 'media':
                if (blank($value)) {
                    $this->requireIf($field, $path);

                    return null;
                }

                if (! isset($this->mediaIds[(int) $value])) {
                    $this->errors[$path] = "A imagem escolhida em «{$label}» já não existe na biblioteca.";

                    return null;
                }

                return (int) $value;

            case 'icon':
                return is_string($value) && in_array($value, $this->icons, true) ? $value : null;

            case 'select':
            case 'buttons':
                if ($value === null || $value === '') {
                    return $field['default'] ?? null;
                }

                // Lista dinâmica (categorias): aceita ids numéricos, a opção pode ter sido apagada.
                if (is_string($field['options'] ?? null) || $field['options'] === []) {
                    return is_numeric($value) ? (int) $value : null;
                }

                foreach ($field['options'] as $option) {
                    if ((string) $option['value'] === (string) $value) {
                        return $option['value'];
                    }
                }

                $this->errors[$path] = "Escolha uma opção válida em «{$label}».";

                return $field['default'] ?? null;

            case 'toggle':
                return filter_var($value, FILTER_VALIDATE_BOOL);

            case 'link':
                return $this->link($value);

            case 'button':
                $link = $this->link($value) ?? ['type' => 'route', 'route' => null, 'page_id' => null, 'url' => null, 'new_tab' => false];
                $text = is_array($value) && is_scalar($value['label'] ?? null) ? trim((string) $value['label']) : '';

                return ['label' => $text !== '' ? mb_substr($text, 0, 80) : null, ...$link];

            case 'list':
                $items = array_values(array_filter(array_map(
                    fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                    is_array($value) ? $value : [],
                ), fn (string $item) => $item !== ''));

                return $this->limit($field, $items, $path);

            case 'repeater':
                $items = [];
                foreach (array_values(is_array($value) ? $value : []) as $i => $item) {
                    $items[] = $this->fields($field['fields'], is_array($item) ? $item : [], "{$path}.{$i}");
                }

                return $this->limit($field, $items, $path);
        }

        return null;
    }

    /**
     * Ligação no formato de App\Support\Links.
     *
     * @return array<string, mixed>|null
     */
    private function link(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $type = in_array($value['type'] ?? null, ['route', 'page', 'url'], true) ? $value['type'] : 'route';

        return [
            'type' => $type,
            'route' => $type === 'route' && is_string($value['route'] ?? null) ? $value['route'] : null,
            'page_id' => $type === 'page' && is_numeric($value['page_id'] ?? null) ? (int) $value['page_id'] : null,
            'url' => $type === 'url' && is_string($value['url'] ?? null) && ($url = trim($value['url'])) !== '' ? mb_substr($url, 0, 500) : null,
            'new_tab' => filter_var($value['new_tab'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * Aparência comum a todos os blocos.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function appearance(array $data, array $definition, string $path): array
    {
        $anchor = is_scalar($data['anchor'] ?? null) ? trim((string) $data['anchor']) : '';

        if ($anchor !== '' && ! preg_match('/^[A-Za-z0-9_-]{1,50}$/', $anchor)) {
            $this->errors["{$path}.anchor"] = 'A âncora só pode ter letras, números, hífenes e sublinhados (até 50).';
        }

        return [
            'is_active' => array_key_exists('is_active', $data) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOL) : ($definition['active'] ?? true),
            'background' => array_key_exists($data['background'] ?? null, BlockSchema::BACKGROUNDS) ? $data['background'] : ($definition['background'] ?? 'white'),
            'anchor' => $anchor !== '' ? $anchor : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function requireIf(array $field, string $path): void
    {
        if (! empty($field['required'])) {
            $this->errors[$path] = "Preencha «{$field['label']}».";
        }
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function limit(array $field, array $items, string $path): array
    {
        if (! empty($field['max_items']) && count($items) > $field['max_items']) {
            $this->errors[$path] = "«{$field['label']}» aceita no máximo {$field['max_items']}.";
        }

        return $items;
    }
}
