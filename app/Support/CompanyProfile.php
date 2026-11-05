<?php

namespace App\Support;

use App\Settings\GeneralSettings;

/**
 * Dados da empresa para o layout público, a partir das Definições do site.
 */
class CompanyProfile
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(private GeneralSettings $settings) {}

    /**
     * @return array{
     *     name: string, short_name: string, tagline: string, about: string,
     *     logo: string, logo_white: string, favicon: ?string, address: string, address_short: string,
     *     phones: array<int, string>, email: string, whatsapp: ?string, hours: string,
     *     map_embed_url: ?string, social: array<string, string>
     * }
     */
    public function toArray(): array
    {
        return $this->data ??= $this->build();
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $settings = $this->settings;

        return [
            'name' => $settings->site_name,
            'short_name' => 'APE',
            'tagline' => localized($settings->tagline),
            'about' => localized($settings->footer_about),
            'logo' => $this->mediaUrl($settings->logo_id) ?? asset('images/brand/logo-ape.png'),
            'logo_white' => $this->mediaUrl($settings->logo_white_id) ?? asset('images/brand/logo-ape.png'),
            'favicon' => $this->mediaUrl($settings->favicon_id),
            'address' => $settings->address,
            'address_short' => $settings->address_short,
            'phones' => array_values(array_filter($settings->phones)),
            'email' => $settings->email,
            'whatsapp' => $settings->whatsapp,
            'hours' => localized($settings->hours),
            'map_embed_url' => $settings->map_embed_url ?: 'https://maps.google.com/maps?q='.rawurlencode($settings->address).'&t=m&z=15&output=embed',
            'forms_recipient_email' => $settings->forms_recipient_email,
            'nuit' => $settings->nuit,
            'commercial_registry' => $settings->commercial_registry,
            'social' => array_filter([
                'facebook' => $settings->facebook,
                'linkedin' => $settings->linkedin,
                'instagram' => $settings->instagram,
            ]),
        ];
    }

    private function mediaUrl(?int $id): ?string
    {
        return app(MediaLookup::class)->find($id)?->url;
    }
}
