<?php

namespace App\Support;

use App\Settings\SeoSettings;

/**
 * Imagem de partilha (Open Graph / Twitter): 1200×630 em JPG, o formato
 * que todas as redes sociais aceitam. Ordem: imagem do conteúdo, imagem
 * por omissão das Definições SEO, imagem da marca em public/images.
 */
class SocialImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    public static function url(?int $mediaId = null): string
    {
        foreach ([$mediaId, app(SeoSettings::class)->og_image_id] as $id) {
            if ($url = static::fromMedia($id)) {
                return $url;
            }
        }

        return asset('images/og-default.jpg');
    }

    private static function fromMedia(?int $id): ?string
    {
        $media = app(MediaLookup::class)->find($id);

        // SVG não é aceite pelas redes sociais.
        if (! $media || strtolower((string) $media->ext) === 'svg') {
            return null;
        }

        return url($media->glideUrl([
            'w' => static::WIDTH,
            'h' => static::HEIGHT,
            'fit' => 'crop',
            'fm' => 'jpg',
            'q' => 80,
        ]));
    }
}
