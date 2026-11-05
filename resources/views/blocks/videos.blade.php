@php
    $lookup = app(\App\Support\MediaLookup::class);

    // O vídeo da biblioteca tem prioridade sobre um endereço externo.
    $items = collect($data['items'] ?? [])
        ->map(fn (array $item) => [...$item, 'src' => $lookup->find($item['video'] ?? null)?->url ?: ($item['video_url'] ?? null)])
        ->filter(fn (array $item) => filled($item['src']))
        ->values();
@endphp

{{-- Vídeos com capa: o ficheiro só é pedido quando o visitante carrega em reproduzir. --}}
@if ($items->isNotEmpty())
    <div class="container-site" x-data="{ video: null, title: '' }">
        <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($items as $item)
                <button type="button" class="video-card" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4) * 100 }}"
                        x-on:click="video = @js($item['src']); title = @js($item['title'] ?? '')"
                        aria-label="{{ __('Ver vídeo') }}: {{ $item['title'] ?? '' }}">
                    <span class="video-card__media">
                        <x-media :id="$item['cover'] ?? null" :widths="[800, 480]" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" class="video-card__image" alt="" />
                        <span class="video-card__play"><x-heroicon-s-play class="size-5" /></span>
                    </span>
                    <span class="video-card__title">{{ $item['title'] ?? '' }}</span>
                </button>
            @endforeach
        </div>

        <div x-show="video" x-cloak x-transition.opacity x-on:keydown.escape.window="video = null"
             class="fixed inset-0 z-[70] flex items-center justify-center bg-dark-900/85 p-4" role="dialog" aria-modal="true" :aria-label="title">
            <div class="relative w-full max-w-4xl" x-on:click.outside="video = null">
                <button type="button" x-on:click="video = null" class="absolute -top-11 right-0 text-white" aria-label="{{ __('Fechar') }}">
                    <x-heroicon-o-x-mark class="size-8" />
                </button>
                <template x-if="video">
                    <video :src="video" class="aspect-video w-full rounded-card bg-black" controls autoplay playsinline></video>
                </template>
            </div>
        </div>
    </div>
@endif
