@php
    use App\Support\IconOptions;

    $count = max(1, count($data['items'] ?? []));
    $grid = match (true) {
        $count >= 4 => 'sm:grid-cols-2 lg:grid-cols-4',
        $count === 3 => 'sm:grid-cols-3',
        $count === 2 => 'sm:grid-cols-2',
        default => '',
    };
@endphp

<div class="container-site">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <div class="grid gap-[30px] {{ $grid }}">
        @foreach ($data['items'] ?? [] as $item)
            <div class="counter" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                @if (filled($item['icon'] ?? null))
                    <div class="counter__icon">
                        <x-dynamic-component :component="IconOptions::component($item['icon'])" class="size-8" />
                    </div>
                @endif

                <div class="counter__number" x-data="counter({{ (int) ($item['value'] ?? 0) }})">
                    <span>{{ $item['prefix'] ?? '' }}</span><span x-text="current">{{ (int) ($item['value'] ?? 0) }}</span><span>{{ $item['suffix'] ?? '' }}</span>
                </div>

                <h3 class="counter__label">{{ $item['label'] ?? '' }}</h3>

                @if (filled($item['text'] ?? null))
                    <p class="counter__text">{{ $item['text'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
