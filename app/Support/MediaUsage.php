<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\TeamMember;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;

/**
 * Onde um ficheiro da biblioteca está a ser usado (para avisar antes de o apagar).
 */
class MediaUsage
{
    /** Chaves dos blocos de página que guardam imagens. */
    private const BLOCK_KEYS = ['image', 'background_image', 'media_id', 'photo', 'cover', 'video'];

    /**
     * @return array<int, string>
     */
    public static function for(int $id): array
    {
        $uses = [];
        $title = fn ($record) => $record->getTranslation('title', config('site.default_locale'));

        foreach (Page::where('og_image_id', $id)->get() as $page) {
            $uses[] = 'Página "'.$title($page).'" (imagem de partilha)';
        }

        foreach (Page::whereNotNull('content')->get() as $page) {
            foreach (array_filter((array) $page->getTranslations('content')) as $blocks) {
                if (static::blocksUse((array) $blocks, $id)) {
                    $uses[] = 'Página "'.$title($page).'" (blocos)';
                    break;
                }
            }
        }

        foreach (Service::where('image_id', $id)->get() as $service) {
            $uses[] = $service->kind->getLabel().' "'.$title($service).'"';
        }

        foreach (Post::where('cover_image_id', $id)->get() as $post) {
            $uses[] = 'Artigo "'.$title($post).'"';
        }

        foreach (TeamMember::where('photo_id', $id)->get() as $member) {
            $uses[] = 'Equipa: '.$member->name;
        }

        $general = app(GeneralSettings::class);
        foreach (['logo_id' => 'Logótipo', 'logo_white_id' => 'Logótipo branco', 'favicon_id' => 'Ícone do site'] as $key => $label) {
            if ((int) ($general->{$key} ?? 0) === $id) {
                $uses[] = "Definições: {$label}";
            }
        }

        if ((int) (app(SeoSettings::class)->og_image_id ?? 0) === $id) {
            $uses[] = 'Definições SEO: imagem de partilha';
        }

        return array_values(array_unique($uses));
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function blocksUse(array $data, int $id): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (static::blocksUse($value, $id)) {
                    return true;
                }
            } elseif (in_array($key, self::BLOCK_KEYS, true) && (int) $value === $id) {
                return true;
            }
        }

        return false;
    }
}
