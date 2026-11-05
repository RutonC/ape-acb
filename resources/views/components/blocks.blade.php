@props(['blocks' => []])

{{-- Desenha os blocos de uma página (construtor do painel). Blocos sem conteúdo
     (ex.: equipa ou blog ainda vazios) não geram secção. --}}
@foreach ($blocks as $block)
    @php
        $view = \App\Support\PageBlocks::view($block['type'] ?? '');
        $data = $block['data'] ?? [];
        $background = in_array($data['background'] ?? null, ['white', 'light', 'dark'], true) ? $data['background'] : 'white';
        $html = $view && view()->exists($view)
            ? trim(view($view, ['data' => $data, 'background' => $background, 'company' => $company ?? app(\App\Support\CompanyProfile::class)->toArray()])->render())
            : '';
    @endphp

    @if ($html !== '')
        <section
            @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif
            @class([
                'block',
                'block--'.$background,
                'block--padded' => ! in_array($block['type'], ['hero', 'cta'], true),
            ])
            data-block="{{ $block['type'] }}"
        >
            {!! $html !!}
        </section>
    @endif
@endforeach
