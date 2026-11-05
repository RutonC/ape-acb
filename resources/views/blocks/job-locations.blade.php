@php
    $locations = \App\Support\JobLocations::all();
@endphp

{{-- Vagas por local: só os locais que existem nas vagas. --}}
@if ($locations->isNotEmpty())
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($locations as $location)
                <a href="{{ lroute('jobs') }}?local={{ urlencode($location['name']) }}" class="location-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                    <span class="location-card__icon"><x-heroicon-o-map-pin class="size-6" /></span>
                    <span>
                        <span class="location-card__name">{{ $location['name'] }}</span>
                        <span class="location-card__count">{{ trans_choice(':count vaga|:count vagas', $location['total']) }}, {{ trans_choice(':count aberta|:count abertas', $location['open']) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
