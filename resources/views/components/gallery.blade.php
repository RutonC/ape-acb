@props([
    // Lista de ['image' => id da média, 'caption' => ?string, 'category' => ?string]
    'items' => [],
    'columns' => 3,
    'filters' => true,
])

@php
    $items = array_values(array_filter($items, fn (array $item) => filled($item['image'] ?? null)));
    $categories = array_values(array_unique(array_filter(array_map(fn (array $item) => trim((string) ($item['category'] ?? '')), $items))));
    $showFilters = $filters && count($categories) > 1;
    app(\App\Support\MediaLookup::class)->prime(array_column($items, 'image'));
    $gridColumns = match ((int) $columns) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp

{{-- Galeria com filtros e ampliação (setas, Esc e foco preso no lightbox) --}}
@if ($items !== [])
    <div x-data="gallery" {{ $attributes->class('gallery') }}>
        @if ($showFilters)
            <div class="portfolio-filters mb-10 !justify-center" data-aos="fade-up">
                <div class="portfolio-filters__group" role="group" aria-label="{{ __('Filtrar imagens') }}">
                    <button type="button" class="portfolio-filter" x-bind:class="{ 'is-active': filter === null }" x-bind:aria-pressed="filter === null" x-on:click="filter = null">{{ __('Todas') }}</button>
                    @foreach ($categories as $category)
                        <button type="button" class="portfolio-filter" x-bind:class="{ 'is-active': filter === @js($category) }" x-bind:aria-pressed="filter === @js($category)" x-on:click="filter = @js($category)">{{ $category }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        <ul class="grid gap-5 {{ $gridColumns }}" role="list">
            @foreach ($items as $index => $item)
                <li data-gallery-item data-category="{{ trim((string) ($item['category'] ?? '')) }}" x-show="isVisible({{ $index }})" x-transition.opacity.duration.300ms>
                    <button type="button" class="gallery__thumb group" x-on:click="open({{ $index }}, $el)"
                            aria-label="{{ __('Ampliar imagem') }}{{ filled($item['caption'] ?? null) ? ': '.$item['caption'] : ' '.($index + 1) }}">
                        <span class="block overflow-hidden">
                            <x-media :id="$item['image']" :widths="[800, 600, 400]" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw"
                                     class="aspect-[4/3] w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        </span>
                        @if (filled($item['caption'] ?? null) || filled($item['category'] ?? null))
                            <span class="gallery__caption">
                                @if (filled($item['category'] ?? null))
                                    <span class="gallery__category">{{ $item['category'] }}</span>
                                @endif
                                {{ $item['caption'] ?? '' }}
                            </span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>

        <div x-show="current !== null" x-cloak x-transition.opacity x-trap.noscroll.inert="current !== null"
             x-on:keydown.escape.window="close()" x-on:keydown.arrow-right.window="next()" x-on:keydown.arrow-left.window="previous()"
             class="gallery__lightbox" role="dialog" aria-modal="true" aria-label="{{ __('Imagem ampliada') }}">
            <button type="button" class="gallery__control top-5 right-5" x-on:click="close()" aria-label="{{ __('Fechar') }}">
                <x-heroicon-o-x-mark class="size-7" />
            </button>
            <button type="button" class="gallery__control top-1/2 left-3 -translate-y-1/2 md:left-6" x-on:click="previous()" aria-label="{{ __('Imagem anterior') }}">
                <x-heroicon-o-chevron-left class="size-7" />
            </button>
            <button type="button" class="gallery__control top-1/2 right-3 -translate-y-1/2 md:right-6" x-on:click="next()" aria-label="{{ __('Imagem seguinte') }}">
                <x-heroicon-o-chevron-right class="size-7" />
            </button>

            @foreach ($items as $index => $item)
                <figure x-show="current === {{ $index }}" class="flex max-h-full max-w-6xl flex-col items-center" x-on:click.outside="close()">
                    <x-media :id="$item['image']" :widths="[1920, 1280, 800]" sizes="90vw" class="max-h-[78vh] w-auto rounded-card object-contain" />
                    <figcaption class="mt-4 text-center text-white">
                        @if (filled($item['caption'] ?? null))
                            <span class="block text-lg">{{ $item['caption'] }}</span>
                        @endif
                        <span class="text-sm text-white/60" x-text="position()"></span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
@endif
