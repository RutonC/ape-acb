@props(['content' => null])

{{-- Conteúdo do editor de texto (HTML sanitizado). --}}
@if (filled($content))
    <div {{ $attributes->merge(['class' => 'rich-content']) }}>
        {!! \App\Support\RichContent::toHtml($content) !!}
    </div>
@endif
