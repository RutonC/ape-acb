@php
    use App\Enums\ServiceKind;
    use App\Support\IconOptions;

    /** @var \App\Models\Service $service */
    $locale = app()->getLocale();
    $title = $service->getTranslation('title', $locale);
    $indexLabel = __('Serviços');
    $audience = $service->getTranslation('audience', $locale);
    $timeline = $service->getTranslation('timeline', $locale);
    $sections = $service->detailSections();

    $othersBlock = [
        'type' => 'cards',
        'data' => [
            'title' => __('Outros serviços'),
            'heading_align' => 'center',
            'style' => 'product',
            'columns' => 3,
            'background' => 'white',
            'items' => $others->map(fn ($item) => [
                'icon' => $item->icon,
                'title' => $item->getTranslation('title', $locale),
                'text' => $item->getTranslation('summary', $locale),
                'link' => ['type' => 'url', 'url' => $item->url()],
            ])->all(),
        ],
    ];

@endphp

<x-layouts.site
    :title="$title"
    :full-title="$service->getTranslation('meta_title', $locale) ?: null"
    :description="$service->getTranslation('meta_description', $locale) ?: $service->getTranslation('summary', $locale)"
    :image="$service->image_id"
>
    <x-page-banner :title="$title" :breadcrumbs="[['label' => $indexLabel, 'url' => lroute($kind->indexRoute())], ['label' => $title]]" />

    {{-- Introdução (rs-heading + texto + botão + imagem) --}}
    <section class="block block--white block--padded" data-block="service-intro">
        <div class="container-site grid items-center gap-14 lg:grid-cols-2">
            <div data-aos="fade-up">
                <x-section-heading :subtitle="$title" :title="$service->getTranslation('tagline', $locale)" align="left" class="!mb-6" />

                @if (filled($service->getTranslation('intro', $locale)))
                    <p class="mb-8 text-lg leading-[1.8]">{{ $service->getTranslation('intro', $locale) }}</p>
                @endif

                @if ($audience || $timeline)
                    <ul class="service-facts mb-10" role="list">
                        @if ($audience)
                            <li class="service-fact">
                                <x-heroicon-o-user-group class="service-fact__icon" aria-hidden="true" />
                                <div>
                                    <p class="service-fact__label">{{ __('Para quem') }}</p>
                                    <p class="service-fact__value">{{ $audience }}</p>
                                </div>
                            </li>
                        @endif
                        @if ($timeline)
                            <li class="service-fact">
                                <x-heroicon-o-clock class="service-fact__icon" aria-hidden="true" />
                                <div>
                                    <p class="service-fact__label">{{ __('Duração') }}</p>
                                    <p class="service-fact__value">{{ $timeline }}</p>
                                </div>
                            </li>
                        @endif
                    </ul>
                @endif

                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ lroute('companies') }}" class="btn btn-primary">{{ __('Inscrever minha empresa') }}</a>
                    @if ($service->external_url)
                        <a href="{{ $service->external_url }}" target="_blank" rel="noopener" class="btn btn-outline">
                            {{ $service->external_label ?: __('Visitar o site') }}
                            <x-heroicon-o-arrow-top-right-on-square class="size-4" aria-hidden="true" />
                        </a>
                    @endif
                    @if ($company['phones'][0] ?? null)
                        <a href="tel:{{ preg_replace('/\s+/', '', $company['phones'][0]) }}" class="inline-flex items-center gap-3 font-heading font-semibold text-heading transition-colors hover:text-primary-500" data-track="phone-click">
                            <span class="inline-flex size-11 items-center justify-center rounded-full bg-primary-50 text-primary-500">
                                <x-heroicon-s-phone class="size-5" />
                            </span>
                            {{ $company['phones'][0] }}
                        </a>
                    @endif
                </div>
            </div>

            <div data-aos="fade-left">
                @if ($service->image_id)
                    <x-media :id="$service->image_id" :widths="[1200, 800, 600]" sizes="(min-width: 1024px) 50vw, 100vw" loading="eager" class="w-full rounded-card shadow-card" />
                @else
                    <div class="service-visual">
                        <div class="service-visual__icon">
                            <x-dynamic-component :component="IconOptions::component($service->icon)" class="size-16" />
                        </div>
                        <p class="service-visual__title">{{ $title }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Detalhes (rs-service-grid) --}}
    @if ($sections !== [])
        <section class="block block--light block--padded" data-block="service-details">
            <div class="container-site">
                <x-section-heading :title="__('O que oferecemos')" />

                <div @class(['grid gap-[30px]', 'mx-auto max-w-[760px]' => count($sections) === 1, 'md:grid-cols-2' => count($sections) > 1 && count($sections) % 2 === 0, 'md:grid-cols-2 lg:grid-cols-3' => count($sections) > 1 && count($sections) % 2 !== 0])>
                    @foreach ($sections as $section)
                        <article class="card card--service" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                            <div class="card__icon">
                                <x-dynamic-component :component="IconOptions::component($section['icon'] ?? null)" class="size-9" />
                            </div>
                            <h3 class="card__title">{{ $section['title'] ?? '' }}</h3>
                            <x-rich-content :content="$section['content'] ?? null" class="card__text !text-base" />
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($others->isNotEmpty())
        <x-blocks :blocks="[$othersBlock]" />
    @endif

</x-layouts.site>
