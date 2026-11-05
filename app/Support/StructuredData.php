<?php

namespace App\Support;

use App\Models\Post;
use App\Settings\GeneralSettings;

/**
 * Dados estruturados schema.org (JSON-LD): Organization e WebSite em todas as
 * páginas, BreadcrumbList nas páginas internas e NewsArticle nas notícias.
 */
class StructuredData
{
    public static function organizationId(): string
    {
        return url('/').'/#organization';
    }

    /**
     * @return array<string, mixed>
     */
    public static function site(): array
    {
        $company = app(CompanyProfile::class)->toArray();
        $settings = app(GeneralSettings::class);
        $home = lroute('home');
        $phone = $company['phones'][0] ?? null;

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => trim(explode(',', $company['address'])[0]),
            'addressLocality' => 'Beira',
            'addressRegion' => 'Sofala',
            'addressCountry' => 'MZ',
        ]);

        $organization = array_filter([
            '@type' => 'Organization',
            '@id' => static::organizationId(),
            'name' => $company['name'],
            'alternateName' => 'APE',
            'description' => $company['about'] ?: null,
            'foundingDate' => '2017',
            'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Sofala'],
            'url' => url('/'),
            'logo' => $company['logo'],
            'email' => $company['email'],
            'telephone' => $phone,
            'address' => $address,
            'taxID' => $settings->nuit,
            'sameAs' => array_values($company['social']) ?: null,
            'contactPoint' => array_map(fn (string $number) => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => $number,
                'email' => $company['email'],
                'areaServed' => 'MZ',
                'availableLanguage' => ['Portuguese'],
            ], $company['phones']),
        ]);

        $website = [
            '@type' => 'WebSite',
            '@id' => url('/').'/#website',
            'url' => $home,
            'name' => $company['short_name'],
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => static::organizationId()],
        ];

        return ['@context' => 'https://schema.org', '@graph' => [$organization, $website]];
    }

    /**
     * @param  array<int, array{label: string, url?: string|null}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [['label' => __('Início'), 'url' => lroute('home')], ...$crumbs];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['label'],
                // O último elemento é a página actual.
                'item' => $index === count($items) - 1 ? url()->current() : ($crumb['url'] ?? null),
            ], $items, array_keys($items)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(Post $post): array
    {
        $locale = app()->getLocale();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->getTranslation('title', $locale),
            'description' => $post->getTranslation('excerpt', $locale) ?: null,
            'image' => SocialImage::url($post->cover_image_id),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'inLanguage' => $locale,
            'mainEntityOfPage' => $post->url(),
            // A autoria é da associação: o utilizador do painel não é necessariamente o autor.
            'author' => ['@id' => static::organizationId()],
            'publisher' => ['@id' => static::organizationId()],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function toScript(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        return '<script type="application/ld+json">'.$json.'</script>';
    }

    /**
     * Vaga aberta, para os motores de busca (só com os dados preenchidos).
     *
     * @return array<string, mixed>
     */
    public static function jobPosting(\App\Models\JobOpening $job): array
    {
        $locale = app()->getLocale();
        $company = app(CompanyProfile::class)->toArray();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->getTranslation('title', $locale),
            'description' => $job->getTranslation('description', $locale) ?: $job->getTranslation('summary', $locale),
            'datePosted' => $job->posted_at?->toDateString(),
            'validThrough' => $job->closes_at?->toDateString(),
            'url' => $job->url(),
            'hiringOrganization' => ['@type' => 'Organization', 'name' => $company['name'], 'sameAs' => url('/')],
            'jobLocation' => ['@type' => 'Place', 'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $job->getTranslation('location', $locale) ?: 'Beira',
                'addressCountry' => 'MZ',
            ])],
        ], fn ($value) => filled($value));
    }
}
