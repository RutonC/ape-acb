@props([
    'title' => null,
    'fullTitle' => null,
    'description' => null,
    'noindex' => false,
    'image' => null,
    'type' => 'website',
    'publishedAt' => null,
])

@php
    $seo = app(\App\Settings\SeoSettings::class);
    $pageTitle = $fullTitle ?: ($title ? $title.' | '.$company['short_name'] : localized($seo->default_title));
    $description ??= localized($seo->default_description);

    // Páginas seguintes de uma listagem: título e canónico próprios.
    $pageNumber = max(1, (int) request()->query('page', 1));
    if ($pageNumber > 1) {
        $pageTitle .= ' | '.__('Página :n', ['n' => $pageNumber]);
    }
    $canonical = url()->current().($pageNumber > 1 ? '?page='.$pageNumber : '');
    $ogLocales = ['pt' => 'pt_PT', 'en' => 'en_GB'];
    $currentLocale = app()->getLocale();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        <meta name="theme-color" content="#302959">
        <script>try { if (localStorage.getItem('ape-theme') === 'dark') document.documentElement.dataset.theme = 'dark'; } catch (e) {}</script>
        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
        @endif
        @if ($seo->ga4_measurement_id)
            {{-- Lido por resources/js/components/analytics.js; só carrega após consentimento --}}
            <meta name="ga4-id" content="{{ $seo->ga4_measurement_id }}">
        @endif
        @if ($seo->google_site_verification)
            <meta name="google-site-verification" content="{{ $seo->google_site_verification }}">
        @endif

        @unless ($noindex)
            <link rel="canonical" href="{{ $canonical }}">
            @foreach (array_keys(config('site.locales')) as $locale)
                <link rel="alternate" hreflang="{{ $locale }}" href="{{ \App\Support\Localization::switchUrl($locale) }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ \App\Support\Localization::switchUrl(\App\Support\Localization::defaultLocale()) }}">
        @endunless

        {{-- Partilha em redes sociais --}}
        <meta property="og:site_name" content="{{ $company['short_name'] }}">
        <meta property="og:type" content="{{ $type }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:image" content="{{ \App\Support\SocialImage::url($image) }}">
        <meta property="og:image:width" content="{{ \App\Support\SocialImage::WIDTH }}">
        <meta property="og:image:height" content="{{ \App\Support\SocialImage::HEIGHT }}">
        <meta property="og:locale" content="{{ $ogLocales[$currentLocale] ?? $currentLocale }}">
        @foreach (array_diff(array_keys(config('site.locales')), [$currentLocale]) as $alternate)
            <meta property="og:locale:alternate" content="{{ $ogLocales[$alternate] ?? $alternate }}">
        @endforeach
        @if ($publishedAt)
            <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">

        {!! \App\Support\StructuredData::toScript(\App\Support\StructuredData::site()) !!}
        @stack('structured-data')

        @if ($company['favicon'])
            <link rel="icon" href="{{ $company['favicon'] }}">
        @else
            <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
            <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
        @endif
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white">
        <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:shadow">
            {{ __('Saltar para o conteúdo') }}
        </a>

        @include('partials.site.header')

        <main id="conteudo">
            {{ $slot }}
        </main>

        @include('partials.site.footer')
        @include('partials.site.floating-actions')
        @include('partials.site.cookie-consent')
    </body>
</html>
