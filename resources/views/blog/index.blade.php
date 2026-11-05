@php
    $locale = app()->getLocale();
    $breadcrumbs = isset($crumb)
        ? [['label' => __('Notícias'), 'url' => lroute('blog')], ['label' => $heading]]
        : [['label' => $heading]];
@endphp

<x-layouts.site
    :title="$heading"
    :full-title="$page?->getTranslation('meta_title', $locale) ?: null"
    :image="$page?->og_image_id"
    :noindex="request()->filled('q')"
    :description="$page?->getTranslation('meta_description', $locale) ?: __('Notícias e actividades da Agência Privada de Emprego e da Associação Comercial da Beira.')"
>
    <x-page-banner :title="$heading" :breadcrumbs="$breadcrumbs" />

    <section class="block block--light block--padded" data-block="blog-list">
        <div class="container-site grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div>
                @if ($showIntro)
                    <x-section-heading
                        :subtitle="__('Nossas notícias')"
                        :title="__('Notícias')"
                        :description="__('Acompanhe as actividades da APE e da Associação Comercial da Beira.')"
                        align="left"
                    />
                @endif

                @if ($posts->isEmpty())
                    <div class="rounded-card bg-white px-6 py-16 text-center shadow-card">
                        <x-heroicon-o-newspaper class="mx-auto mb-4 size-12 text-primary-500" />
                        <p class="text-lg">
                            {{ $search !== '' ? __('Nenhuma notícia encontrada para esta pesquisa.') : __('Ainda não há notícias publicadas.') }}
                        </p>
                    </div>
                @else
                    <div class="space-y-10">
                        @foreach ($posts as $post)
                            <x-post-card :post="$post" data-aos="fade-up" />
                        @endforeach
                    </div>

                    @include('partials.site.pagination', ['paginator' => $posts])
                @endif
            </div>

            @include('blog.sidebar')
        </div>
    </section>
</x-layouts.site>
