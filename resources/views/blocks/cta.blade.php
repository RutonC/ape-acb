@php
    $phone = ($data['phone'] ?? null) ?: ($company['phones'][0] ?? null);
    $isCall = ($data['style'] ?? 'band') === 'call';
@endphp

<div @class(['cta', 'cta--call' => $isCall])>

    <div class="container-site relative">
        @if ($isCall)
            <div class="mx-auto max-w-[760px] text-center" data-aos="fade-up">
                @if ($phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="cta__phone-icon" aria-label="{{ __('Ligar') }}" data-track="phone-click">
                        <x-heroicon-s-phone class="size-7" />
                    </a>
                @endif
                @if (filled($data['subtitle'] ?? null))
                    <span class="cta__subtitle">{{ $data['subtitle'] }}</span>
                @endif
                @if ($phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="cta__phone" data-track="phone-click">{{ $phone }}</a>
                @endif
                <h2 class="cta__title">{{ $data['title'] ?? '' }}</h2>
                @if (filled($data['text'] ?? null))
                    <p class="cta__text">{{ $data['text'] }}</p>
                @endif
                <x-link-button :link="$data['button'] ?? null" :label="$data['button']['label'] ?? null" class="btn btn-white mt-8" />
            </div>
        @else
            <div class="flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center" data-aos="fade-up">
                <div class="max-w-[720px]">
                    @if (filled($data['subtitle'] ?? null))
                        <span class="cta__subtitle">{{ $data['subtitle'] }}</span>
                    @endif
                    <h2 class="cta__title">{{ $data['title'] ?? '' }}</h2>
                    @if (filled($data['text'] ?? null))
                        <p class="cta__text">{{ $data['text'] }}</p>
                    @endif
                </div>
                <x-link-button :link="$data['button'] ?? null" :label="$data['button']['label'] ?? null" class="btn btn-white shrink-0" />
            </div>
        @endif
    </div>
</div>
