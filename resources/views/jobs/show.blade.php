@php
    /** @var \App\Models\JobOpening $job */
    $locale = app()->getLocale();
    $title = $job->getTranslation('title', $locale);
    $isOpen = $job->isOpen();
    $description = $job->getTranslation('description', $locale);
    $requirements = $job->getTranslation('requirements', $locale);
    $contract = $job->getTranslation('contract_type', $locale);
    $location = $job->getTranslation('location', $locale);

    // Ficha da vaga: só os campos preenchidos.
    $facts = array_filter([
        ['heroicon-o-squares-2x2', __('Categoria'), $job->category?->getTranslation('name', $locale)],
        ['heroicon-o-briefcase', __('Tipo de contrato'), $contract],
        ['heroicon-o-user-group', __('Número de vagas'), $job->positions],
        ['heroicon-o-academic-cap', __('Experiência'), $job->experience],
        ['heroicon-o-banknotes', __('Salário'), $job->salary],
        ['heroicon-o-map-pin', __('Local'), $location],
        ['heroicon-o-calendar-days', __('Publicada em'), $job->posted_at?->format('d/m/Y')],
        ['heroicon-o-clock', __('Data limite'), $job->closes_at?->format('d/m/Y')],
    ], fn (array $fact) => filled($fact[2]));
@endphp

<x-layouts.site :title="$title" :description="$job->getTranslation('summary', $locale)" :image="$job->cover_image_id" :noindex="! $isOpen">
    @if ($isOpen)
        @push('structured-data')
            {!! \App\Support\StructuredData::toScript(\App\Support\StructuredData::jobPosting($job)) !!}
        @endpush
    @endif

    @push('structured-data')
        {!! \App\Support\StructuredData::toScript(\App\Support\StructuredData::breadcrumbs([['label' => __('Vagas'), 'url' => lroute('jobs')], ['label' => $title]])) !!}
    @endpush

    <div class="container-site pt-6">
        @if ($job->cover_image_id)
            <div class="job-cover">
                <x-media :id="$job->cover_image_id" :widths="[1400, 1000, 700]" sizes="(min-width: 1280px) 1250px, 100vw" loading="eager" class="job-cover__image" />
            </div>
        @endif

        <nav aria-label="{{ __('Trilho de navegação') }}" class="job-breadcrumbs">
            <a href="{{ lroute('home') }}">{{ __('Início') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ lroute('jobs') }}">{{ __('Vagas') }}</a>
        </nav>

        <header class="job-head">
            <div class="min-w-0">
                <h1 class="job-head__title">{{ $title }}</h1>
                <ul class="job-card__meta !mb-0">
                    @if ($contract)
                        <li><x-heroicon-o-briefcase class="size-4" /> {{ $contract }}</li>
                    @endif
                    @if ($job->posted_at)
                        <li><x-heroicon-o-clock class="size-4" /> {{ $job->posted_at->format('d/m/Y') }}</li>
                    @endif
                    <li><span @class(['job-card__state', 'is-open' => $isOpen])>{{ $isOpen ? __('Aberta') : __('Encerrada') }}</span></li>
                </ul>
            </div>
            @if ($isOpen)
                <a href="#candidatura" class="btn btn-primary shrink-0"><x-heroicon-o-paper-airplane class="size-4" /> {{ __('Candidatar-me') }}</a>
            @else
                <a href="{{ lroute('apply') }}" class="btn btn-outline shrink-0">{{ __('Candidatura espontânea') }}</a>
            @endif
        </header>
    </div>

    <section class="block block--white pb-16" data-block="job">
        <div class="container-site grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="min-w-0">
                @if ($facts !== [])
                    <div class="job-facts">
                        <h2 class="job-facts__title">{{ __('Informação da vaga') }}</h2>
                        <dl class="job-facts__list">
                            @foreach ($facts as [$icon, $label, $value])
                                <div>
                                    <dt><x-dynamic-component :component="$icon" class="size-5" /> {{ $label }}</dt>
                                    <dd>{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                @unless ($isOpen)
                    <p class="job-notice mt-8" role="status">
                        <x-heroicon-o-information-circle class="size-5 shrink-0" />
                        <span>{{ __('Esta vaga já encerrou. Pode enviar-nos o seu currículo para futuras oportunidades.') }} <a href="{{ lroute('apply') }}">{{ __('Candidatura espontânea') }}</a></span>
                    </p>
                @endunless

                @if (filled($description))
                    <h2 class="job-section-title">{{ __('Descrição da vaga') }}</h2>
                    <x-rich-content :content="$description" />
                @endif

                @if (filled($requirements))
                    <h2 class="job-section-title">{{ __('Requisitos') }}</h2>
                    <x-rich-content :content="$requirements" />
                @endif

                @if ($isOpen)
                    <div class="mt-12">
                        @include('jobs.partials.application-form', ['opening' => $job])
                    </div>
                @endif
            </div>

            <aside class="space-y-6">
                <div class="side-card">
                    <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="side-card__logo" width="120" height="80" loading="lazy">
                    <p class="mb-4 text-sm">{{ __('Vaga publicada pela APE para um dos seus clientes.') }}</p>
                    <ul class="side-card__list">
                        @foreach ($company['phones'] as $number)
                            <li><x-heroicon-o-phone class="size-4 shrink-0" /> <a href="tel:{{ preg_replace('/\s+/', '', $number) }}">{{ $number }}</a></li>
                        @endforeach
                        @if ($company['email'])
                            <li><x-heroicon-o-envelope class="size-4 shrink-0" /> <a href="mailto:{{ $company['email'] }}" class="break-all">{{ $company['email'] }}</a></li>
                        @endif
                    </ul>
                </div>

                @if ($similar->isNotEmpty())
                    <div class="side-card">
                        <h2 class="side-card__title">{{ __('Vagas semelhantes') }}</h2>
                        <ul class="similar-jobs">
                            @foreach ($similar as $other)
                                <li>
                                    <a href="{{ $other->url() }}">{{ $other->getTranslation('title', $locale) }}</a>
                                    <p>
                                        <span @class(['job-card__state', 'is-open' => $other->isOpen()])>{{ $other->isOpen() ? __('Aberta') : __('Encerrada') }}</span>
                                        @if (filled($other->getTranslation('location', $locale)))
                                            <span>{{ $other->getTranslation('location', $locale) }}</span>
                                        @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.site>
