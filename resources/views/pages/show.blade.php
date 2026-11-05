@php
    /** @var \App\Models\Page $page */
    $title = $page->getTranslation('title', app()->getLocale());
    $metaTitle = $page->getTranslation('meta_title', app()->getLocale()) ?: null;
@endphp

<x-layouts.site
    :full-title="$metaTitle"
    :title="$page->is_homepage ? null : $title"
    :description="$page->getTranslation('meta_description', app()->getLocale()) ?: null"
    :noindex="$preview"
    :image="$page->og_image_id"
>
    @if ($preview)
        <div class="bg-amber-400 px-4 py-2 text-center text-sm font-semibold text-dark-900">
            {{ __('Pré-visualização') }}: {{ $page->status->getLabel() }}
        </div>
    @endif

    @if ($showBanner)
        <x-page-banner :title="$title" :breadcrumbs="[['label' => $title]]" />
    @endif

    <x-blocks :blocks="$blocks" />

    @if ($blocks === [])
        <section class="py-[100px]">
            <div class="container-site text-center">
                <p class="text-lg">{{ __('Esta página ainda não tem conteúdo.') }}</p>
            </div>
        </section>
    @endif
</x-layouts.site>
