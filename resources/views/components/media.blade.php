@props([
    'id' => null,
    'widths' => [1600, 1200, 800, 480],
    'sizes' => '100vw',
    'loading' => 'lazy',
])

@php
    $media = app(\App\Support\MediaLookup::class)->find($id);
@endphp

{{-- Imagem da biblioteca de média: WebP em vários tamanhos (RNF01); SVG servido tal como está. --}}
@if ($media && ! $media->isResizable())
    <img src="{{ $media->url }}" alt="{{ $media->alt }}"
         @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         loading="{{ $loading }}" decoding="async" {{ $attributes }}>
@elseif ($media)
    @php
        $ratio = $media->width && $media->height ? $media->height / $media->width : null;
        $srcset = collect($widths)
            ->map(fn (int $width) => $media->glideUrl([
                'w' => $width,
                'h' => $ratio ? (int) floor($width * $ratio) : null,
                'fm' => 'webp',
                'q' => 80,
            ]).' '.$width.'w')
            ->implode(', ');
    @endphp
    <img src="{{ $media->glideUrl(['fm' => 'webp', 'q' => 80]) }}"
         srcset="{{ $srcset }}" sizes="{{ $sizes }}"
         alt="{{ $media->alt }}"
         @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         loading="{{ $loading }}" decoding="async" {{ $attributes }}>
@endif
