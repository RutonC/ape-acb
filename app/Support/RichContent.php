<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * HTML do editor de texto do painel, sanitizado antes de ser mostrado no
 * site (sem scripts, eventos nem URLs perigosos).
 */
class RichContent
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function toHtml(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return static::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return static::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->allowAttribute('class', '*')
                ->allowAttribute('style', '*')
                ->allowAttribute('data-id', '*')
                ->allowAttribute('data-type', '*')
                ->allowAttribute('width', 'img')
                ->allowAttribute('height', 'img')
                ->allowAttribute('target', 'a')
                ->allowAttribute('rel', 'a')
                ->withMaxInputLength(-1),
        );
    }
}
