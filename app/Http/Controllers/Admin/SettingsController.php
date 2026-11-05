<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use App\Support\Admin\Translations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelSettings\Settings;

/**
 * Definições gerais do site e de SEO (spatie/laravel-settings). Área "settings".
 */
class SettingsController extends Controller
{
    public function showGeneral(GeneralSettings $settings): JsonResponse
    {
        return response()->json(['data' => $this->generalData($settings)]);
    }

    public function updateGeneral(Request $request, GeneralSettings $settings): JsonResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            ...Translations::rules('tagline', ['string', 'max:160'], required: true),
            ...Translations::rules('footer_about', ['string', 'max:600'], required: true),
            'logo_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            'logo_white_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            'favicon_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            'address' => ['required', 'string', 'max:255'],
            'address_short' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phones' => ['required', 'array', 'min:1', 'max:5'],
            'phones.*' => ['required', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            ...Translations::rules('hours', ['string', 'max:160'], required: true),
            'map_embed_url' => ['nullable', 'url:https', 'max:1000'],
            'forms_recipient_email' => ['required', 'email', 'max:190'],
            'nuit' => ['nullable', 'string', 'max:40'],
            'commercial_registry' => ['nullable', 'string', 'max:80'],
            'facebook' => ['nullable', 'url:https,http', 'max:255'],
            'linkedin' => ['nullable', 'url:https,http', 'max:255'],
            'instagram' => ['nullable', 'url:https,http', 'max:255'],
        ], [
            'phones.required' => 'Indique pelo menos um telefone.',
            'phones.min' => 'Indique pelo menos um telefone.',
            'map_embed_url.url' => 'Indique um endereço https completo.',
        ], [
            ...Translations::attributes(['tagline' => 'slogan', 'footer_about' => 'texto do rodapé', 'hours' => 'horário']),
            'site_name' => 'nome da empresa',
            'address' => 'morada',
            'address_short' => 'morada curta',
            'email' => 'e-mail',
            'phones.*' => 'telefone',
            'forms_recipient_email' => 'e-mail dos formulários',
            'map_embed_url' => 'endereço do mapa',
            'commercial_registry' => 'registo comercial',
        ]);

        foreach (['tagline', 'footer_about', 'hours'] as $field) {
            $data[$field] = $this->translated($data[$field]);
        }

        $data['phones'] = array_values(array_filter(array_map('trim', $data['phones'])));

        $this->fill($settings, $data);

        return response()->json(['data' => $this->generalData($settings)]);
    }

    public function showSeo(SeoSettings $settings): JsonResponse
    {
        return response()->json(['data' => $this->seoData($settings)]);
    }

    public function updateSeo(Request $request, SeoSettings $settings): JsonResponse
    {
        $data = $request->validate([
            ...Translations::rules('default_title', ['string', 'max:70'], required: true),
            ...Translations::rules('default_description', ['string', 'max:160'], required: true),
            'og_image_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            'ga4_measurement_id' => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]+$/'],
            'google_site_verification' => ['nullable', 'string', 'max:120'],
        ], [
            'ga4_measurement_id.regex' => 'O ID de medição tem o formato G-XXXXXXXXXX.',
        ], [
            ...Translations::attributes(['default_title' => 'título', 'default_description' => 'descrição']),
            'ga4_measurement_id' => 'ID de medição',
            'google_site_verification' => 'código de verificação',
        ]);

        foreach (['default_title', 'default_description'] as $field) {
            $data[$field] = $this->translated($data[$field]);
        }

        $this->fill($settings, $data);

        return response()->json(['data' => $this->seoData($settings)]);
    }

    /**
     * Um valor por idioma, com os vazios como "" (o site usa o português quando falta).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function translated(array $values): array
    {
        return collect(Translations::locales())
            ->mapWithKeys(fn (string $locale) => [$locale => trim((string) ($values[$locale] ?? ''))])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Settings $settings, array $data): void
    {
        foreach ($data as $property => $value) {
            $settings->{$property} = $value;
        }

        $settings->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function generalData(GeneralSettings $settings): array
    {
        return [
            'site_name' => $settings->site_name,
            'tagline' => $this->translated($settings->tagline),
            'footer_about' => $this->translated($settings->footer_about),
            'logo_id' => $settings->logo_id,
            'logo_white_id' => $settings->logo_white_id,
            'favicon_id' => $settings->favicon_id,
            'address' => $settings->address,
            'address_short' => $settings->address_short,
            'email' => $settings->email,
            'phones' => array_values($settings->phones),
            'whatsapp' => $settings->whatsapp,
            'hours' => $this->translated($settings->hours),
            'map_embed_url' => $settings->map_embed_url,
            'forms_recipient_email' => $settings->forms_recipient_email,
            'nuit' => $settings->nuit,
            'commercial_registry' => $settings->commercial_registry,
            'facebook' => $settings->facebook,
            'linkedin' => $settings->linkedin,
            'instagram' => $settings->instagram,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seoData(SeoSettings $settings): array
    {
        return [
            'default_title' => $this->translated($settings->default_title),
            'default_description' => $this->translated($settings->default_description),
            'og_image_id' => $settings->og_image_id,
            'ga4_measurement_id' => $settings->ga4_measurement_id,
            'google_site_verification' => $settings->google_site_verification,
        ];
    }
}
