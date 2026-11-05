@props(['job', 'layout' => 'grid'])

@php
    /** @var \App\Models\JobOpening $job */
    $locale = app()->getLocale();
    $category = $job->category;
    $location = $job->getTranslation('location', $locale);
    $contract = $job->getTranslation('contract_type', $locale);
    $summary = $job->getTranslation('summary', $locale);
    $isOpen = $job->isOpen();
    $icon = \App\Support\IconOptions::component($category?->icon ?: 'briefcase');
@endphp

{{-- Cartão de vaga, usado na grelha e na lista. --}}
<article {{ $attributes->class(['job-card', 'job-card--list' => $layout === 'list', 'job-card--closed' => ! $isOpen]) }}>
    <div class="job-card__top">
        <span class="job-card__icon"><x-dynamic-component :component="$icon" class="size-6" /></span>
        <div class="min-w-0">
            <p class="job-card__category">{{ $category?->getTranslation('name', $locale) ?? __('Vaga') }}</p>
            @if ($location)
                <p class="job-card__location"><x-heroicon-o-map-pin class="size-4 shrink-0" /> {{ $location }}</p>
            @endif
        </div>
    </div>

    <h3 class="job-card__title"><a href="{{ $job->url() }}">{{ $job->getTranslation('title', $locale) }}</a></h3>

    <ul class="job-card__meta">
        @if ($contract)
            <li><x-heroicon-o-briefcase class="size-4" /> {{ $contract }}</li>
        @endif
        @if ($job->posted_at)
            <li><x-heroicon-o-clock class="size-4" /> <time datetime="{{ $job->posted_at->toDateString() }}">{{ $job->posted_at->format('d/m/Y') }}</time></li>
        @endif
    </ul>

    @if (filled($summary))
        <p class="job-card__summary">{{ $summary }}</p>
    @endif

    <div class="job-card__footer">
        <span @class(['job-card__state', 'is-open' => $isOpen])>{{ $isOpen ? __('Aberta') : __('Encerrada') }}</span>
        <a href="{{ $job->url() }}" class="job-card__action" aria-label="{{ ($isOpen ? __('Candidatar') : __('Ver a vaga')).': '.$job->getTranslation('title', $locale) }}">{{ $isOpen ? __('Candidatar') : __('Ver a vaga') }}</a>
    </div>
</article>
