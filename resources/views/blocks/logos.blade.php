{{-- Parceiros: grelha de cartões com logótipo e nome. --}}
@if (! empty($data['items']))
    <div class="container-site">
        <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        <ul class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            @foreach ($data['items'] as $item)
                <li data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                    @if (filled($item['url'] ?? null))
                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="partner-card">
                            <span class="partner-card__logo"><x-media :id="$item['image'] ?? null" :widths="[400, 200]" sizes="180px" /></span>
                            <span class="partner-card__name">{{ $item['name'] ?? '' }}</span>
                        </a>
                    @else
                        <div class="partner-card">
                            <span class="partner-card__logo"><x-media :id="$item['image'] ?? null" :widths="[400, 200]" sizes="180px" /></span>
                            <span class="partner-card__name">{{ $item['name'] ?? '' }}</span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
