<?php

namespace App\Support;

/**
 * Tipos de bloco do construtor de páginas. Cada tipo é desenhado por
 * resources/views/blocks/{tipo}.blade.php (com "_" trocado por "-").
 */
class PageBlocks
{
    public const TYPES = [
        'job_hero', 'job_categories', 'hiring_banner', 'job_list', 'job_locations',
        'hero', 'cards', 'about', 'counters', 'blog', 'team', 'company_info',
        'process', 'cta', 'testimonials', 'videos', 'contact_form', 'logos', 'gallery', 'pricing', 'faq', 'rich_text',
    ];

    public static function view(string $type): ?string
    {
        return in_array($type, static::TYPES, true) ? 'blocks.'.str_replace('_', '-', $type) : null;
    }
}
