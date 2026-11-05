@php
    use App\Support\IconOptions;
    use App\Support\Links;

    $style = in_array($data['style'] ?? null, ['service', 'feature', 'product'], true) ? $data['style'] : 'service';
    $columns = (int) ($data['columns'] ?? 3);
    $grid = match ($columns) {
        2 => 'md:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        6 => 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<div class="container-site">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <div class="grid gap-[30px] {{ $grid }}">
        @foreach ($data['items'] ?? [] as $item)
            @php
                $url = Links::url($item['link'] ?? null);
                $linkLabel = ($item['link_label'] ?? null) ?: __('Saber mais');
            @endphp

            <article class="card card--{{ $style }} {{ $columns === 6 ? 'card--compact' : '' }}" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4) * 100 }}">
                <div class="card__icon">
                    @if (filled($item['image'] ?? null))
                        <x-media :id="$item['image']" :widths="[160]" sizes="80px" class="size-full object-contain" />
                    @else
                        <x-dynamic-component :component="IconOptions::component($item['icon'] ?? null)" class="size-9" />
                    @endif
                </div>

                <div class="card__body">
                    <h3 class="card__title">
                        @if ($url)
                            <a href="{{ $url }}">{{ $item['title'] ?? '' }}</a>
                        @else
                            {{ $item['title'] ?? '' }}
                        @endif
                    </h3>

                    @if (filled($item['text'] ?? null))
                        <p class="card__text">{{ $item['text'] }}</p>
                    @endif

                    @if ($url && $style !== 'feature')
                        <a href="{{ $url }}" class="card__link">
                            {{ $linkLabel }}
                            <x-heroicon-o-arrow-long-right class="size-5" />
                        </a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</div>
