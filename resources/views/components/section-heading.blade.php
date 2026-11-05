@props([
    'subtitle' => null,
    'title' => null,
    'description' => null,
    'align' => 'center',
    'level' => 'h2',
])

{{-- Cabeçalho de secção: subtítulo, título e descrição. --}}
@if (filled($subtitle) || filled($title) || filled($description))
    <div {{ $attributes->class([
        'section-heading mb-[50px]',
        'mx-auto max-w-[760px] text-center' => $align === 'center',
        'max-w-[680px]' => $align !== 'center',
    ]) }} data-aos="fade-up">
        @if (filled($subtitle))
            <span class="section-heading__sub">{{ $subtitle }}</span>
        @endif
        @if (filled($title))
            <{{ $level }} class="section-heading__title">{{ $title }}</{{ $level }}>
        @endif
        @if (filled($description))
            <p class="section-heading__desc">{{ $description }}</p>
        @endif
    </div>
@endif
