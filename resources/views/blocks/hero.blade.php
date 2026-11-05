@php
    use App\Support\IconOptions;

    $videoEmbed = youtube_embed_url($data['video_url'] ?? null);
    $hasImage = filled($data['image'] ?? null);
@endphp

<div class="hero" x-data="{ video: false }">
    @if (filled($data['background_image'] ?? null))
        <x-media :id="$data['background_image']" :widths="[1920, 1280, 768]" loading="eager" class="hero__bg" alt="" />
    @endif

    <div @class(['container-site relative grid items-center gap-12', 'lg:grid-cols-2 lg:gap-16' => $hasImage])>
        <div>
            @if (filled($data['subtitle'] ?? null))
                <span class="section-heading__sub rise">{{ $data['subtitle'] }}</span>
            @endif

            <h1 class="hero__title rise">{{ $data['title'] ?? '' }}</h1>

            @if (filled($data['text'] ?? null))
                <p class="hero__text rise rise--d1">{{ $data['text'] }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-3 rise rise--d2">
                <x-link-button :link="$data['primary_button'] ?? null" :label="$data['primary_button']['label'] ?? null" />
                <x-link-button :link="$data['secondary_button'] ?? null" :label="$data['secondary_button']['label'] ?? null" class="btn btn-outline" />

                @if ($videoEmbed)
                    <button type="button" x-on:click="video = true" class="video-play" aria-label="{{ __('Ver vídeo') }}">
                        <x-heroicon-s-play class="size-7" />
                    </button>
                @endif
            </div>

            @if (! empty($data['highlights']))
                <ul class="hero__highlights rise rise--d2">
                    @foreach ($data['highlights'] as $item)
                        <li>
                            <span class="hero__highlight-icon">
                                <x-dynamic-component :component="IconOptions::component($item['icon'] ?? null)" class="size-6" />
                            </span>
                            <span>
                                <strong>{{ $item['title'] ?? '' }}</strong>
                                @if (filled($item['text'] ?? null))
                                    <span class="block text-sm">{{ $item['text'] }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($hasImage)
            <div class="hero__media rise rise--left rise--d1">
                <x-media :id="$data['image']" :widths="[1200, 800, 600]" sizes="(min-width: 1024px) 50vw, 100vw" loading="eager" class="hero__image" />

                @if (filled($data['badge_value'] ?? null))
                    <div class="hero__badge">
                        <span class="hero__badge-value">{{ $data['badge_value'] }}</span>
                        <span class="hero__badge-label">{{ $data['badge_label'] ?? '' }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if ($videoEmbed)
        <div x-show="video" x-cloak x-transition.opacity x-on:keydown.escape.window="video = false"
             class="fixed inset-0 z-[70] flex items-center justify-center bg-dark-900/85 p-4" role="dialog" aria-modal="true">
            <div class="relative aspect-video w-full max-w-4xl" x-on:click.outside="video = false">
                <button type="button" x-on:click="video = false" class="absolute -top-11 right-0 text-white" aria-label="{{ __('Fechar') }}">
                    <x-heroicon-o-x-mark class="size-8" />
                </button>
                <template x-if="video">
                    <iframe src="{{ $videoEmbed }}?autoplay=1" class="size-full rounded-card" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="{{ __('Vídeo') }}"></iframe>
                </template>
            </div>
        </div>
    @endif
</div>
