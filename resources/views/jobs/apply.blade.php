@php
    $locale = app()->getLocale();
    $title = $page?->getTranslation('title', $locale) ?: __('Candidate-se já');
@endphp

<x-layouts.site
    :title="$title"
    :full-title="$page?->getTranslation('meta_title', $locale) ?: null"
    :description="$page?->getTranslation('meta_description', $locale) ?: __('Envie o seu currículo à Agência Privada de Emprego, para uma vaga aberta ou como candidatura espontânea.')"
>
    <x-page-banner :title="$title" :breadcrumbs="[['label' => $title]]" />

    <section class="block block--white block--padded" data-block="apply">
        <div class="container-site mx-auto max-w-[920px]">
            @include('jobs.partials.application-form', ['opening' => $selected, 'openings' => $openings])
        </div>
    </section>
</x-layouts.site>
