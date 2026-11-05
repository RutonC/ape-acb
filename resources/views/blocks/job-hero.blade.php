@php
    $categories = \App\Models\JobCategory::query()->orderBy('sort_order')->get();
    $locations = \App\Support\JobLocations::all();
    $popular = \App\Models\JobCategory::query()->whereHas('jobs')->withCount('jobs')->orderByDesc('jobs_count')->limit(6)->get();

    $title = e($data['title'] ?? '');
    $highlight = trim((string) ($data['highlight'] ?? ''));
    if ($highlight !== '') {
        $title = str_replace(e($highlight), '<span class="job-hero__highlight">'.e($highlight).'</span>', $title);
    }
@endphp

{{-- Topo da página inicial: título, pesquisa de vagas e duas fotografias. --}}
<div class="job-hero">
    <div class="container-site job-hero__grid">
        <div class="job-hero__copy rise">
            <h1 class="job-hero__title">{!! $title !!}</h1>

            @if (filled($data['text'] ?? null))
                <p class="job-hero__text">{{ $data['text'] }}</p>
            @endif

            <x-job-search :categories="$categories" :locations="$locations" class="mt-8" />

            @if ($popular->isNotEmpty())
                <p class="job-hero__popular">
                    <strong>{{ __('Mais procuradas:') }}</strong>
                    @foreach ($popular as $category)
                        <a href="{{ $category->url() }}">{{ $category->getTranslation('name', app()->getLocale()) }}</a>@if (! $loop->last),@endif
                    @endforeach
                </p>
            @endif
        </div>

        <div class="job-hero__media rise rise--left" aria-hidden="{{ blank($data['image'] ?? null) ? 'true' : 'false' }}">
            @if (filled($data['image'] ?? null))
                <div class="job-hero__photo job-hero__photo--main">
                    <x-media :id="$data['image']" :widths="[1200, 800, 600]" sizes="(min-width: 1024px) 34vw, 80vw" loading="eager" fetchpriority="high" />
                </div>
            @endif
            @if (filled($data['image_2'] ?? null))
                <div class="job-hero__photo job-hero__photo--second">
                    <x-media :id="$data['image_2']" :widths="[800, 600, 400]" sizes="(min-width: 1024px) 26vw, 60vw" />
                </div>
            @endif
        </div>
    </div>
</div>
