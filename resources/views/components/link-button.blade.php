@props([
    'link' => null,
    'label' => null,
])

@php
    $url = \App\Support\Links::url($link);
@endphp

@if ($url && filled($label))
    <a href="{{ $url }}"
       @if (\App\Support\Links::opensInNewTab($link)) target="_blank" rel="noopener" @endif
       {{ $attributes->merge(['class' => 'btn btn-primary']) }}>{{ $label }}</a>
@endif
