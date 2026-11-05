<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * SEO por omissão e integrações (Google Analytics 4, Search Console).
 */
class SeoSettings extends Settings
{
    /** @var array<string, string> */
    public array $default_title;

    /** @var array<string, string> */
    public array $default_description;

    public ?int $og_image_id;

    public ?string $ga4_measurement_id;

    public ?string $google_site_verification;

    public static function group(): string
    {
        return 'seo';
    }
}
