@php
    $imageRight = ($data['image_position'] ?? 'left') === 'right';
    $hasImage = filled($data['image'] ?? null);
@endphp

<div class="container-site grid items-center gap-14 {{ $hasImage ? 'lg:grid-cols-2' : '' }}">
    @if ($hasImage)
        <div @class(['about__media', 'lg:order-2' => $imageRight]) data-aos="{{ $imageRight ? 'fade-left' : 'fade-right' }}">
            <x-media :id="$data['image']" :widths="[1200, 800, 600]" sizes="(min-width: 1024px) 50vw, 100vw" class="about__image" />

            @if (filled($data['badge_value'] ?? null))
                <div class="about__badge">
                    <span class="about__badge-value">{{ $data['badge_value'] }}</span>
                    <span class="about__badge-label">{{ $data['badge_label'] ?? '' }}</span>
                </div>
            @endif
        </div>
    @endif

    <div data-aos="fade-up">
        <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" align="left" class="!mb-6" />

        <x-rich-content :content="$data['content'] ?? null" class="mb-6" />

        @if (! empty($data['bullets']))
            <ul class="about__list">
                @foreach ($data['bullets'] as $bullet)
                    <li>
                        <x-heroicon-s-check-circle class="size-6 shrink-0 text-primary-500" />
                        <span>{{ $bullet }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-8 flex flex-wrap items-center gap-4">
            <x-link-button :link="$data['button'] ?? null" :label="$data['button']['label'] ?? null" class="btn btn-primary" />
            <x-link-button :link="$data['secondary_button'] ?? null" :label="$data['secondary_button']['label'] ?? null" class="btn btn-outline" />
        </div>
    </div>
</div>
