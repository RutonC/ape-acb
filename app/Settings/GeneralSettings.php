<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Definições gerais do site (identidade, contactos, redes sociais).
 *
 * Os campos em array com chaves de idioma (['pt' => ..., 'en' => ...]) são lidos
 * com localized() no idioma actual.
 */
class GeneralSettings extends Settings
{
    public string $site_name;

    /** @var array<string, string> */
    public array $tagline;

    /** @var array<string, string> */
    public array $footer_about;

    public ?int $logo_id;

    public ?int $logo_white_id;

    public ?int $favicon_id;

    public string $address;

    public string $address_short;

    /** @var array<int, string> */
    public array $phones;

    public string $email;

    public ?string $whatsapp;

    /** @var array<string, string> */
    public array $hours;

    public ?string $map_embed_url;

    public ?string $facebook;

    public ?string $linkedin;

    public ?string $instagram;

    public string $forms_recipient_email;

    public ?string $nuit;

    public ?string $commercial_registry;

    public static function group(): string
    {
        return 'general';
    }
}
