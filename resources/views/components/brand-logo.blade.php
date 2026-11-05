@props([
    'src',
    'name',
    'href',
    'light' => false,
])

{{-- Logótipo oficial da APE, sobre fundo branco para se ler também no modo escuro. --}}
<a href="{{ $href }}" {{ $attributes->class(['brand-logo']) }} aria-label="{{ $name }}">
    <img src="{{ $src }}" alt="{{ $name }}" width="86" height="58">
</a>
