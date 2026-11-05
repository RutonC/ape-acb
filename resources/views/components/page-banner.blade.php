@props([
    'title',
    'breadcrumbs' => [],
    'image' => null,
])

{{-- Topo das páginas internas: trilho de navegação e título --}}
@if ($breadcrumbs)
    @push('structured-data')
        {!! \App\Support\StructuredData::toScript(\App\Support\StructuredData::breadcrumbs($breadcrumbs)) !!}
    @endpush
@endif
<section class="page-banner" @if ($image) style="background-image: url('{{ $image }}')" @endif>
    <div class="container-site relative">
        <nav aria-label="{{ __('Trilho de navegação') }}" class="breadcrumbs rise">
            <ol class="flex flex-wrap items-center gap-x-2">
                <li><a href="{{ lroute('home') }}">{{ __('Início') }}</a></li>
                @foreach ($breadcrumbs as $crumb)
                    <li aria-hidden="true">&middot;</li>
                    <li>
                        @if (! empty($crumb['url']) && ! $loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                        @else
                            <span aria-current="page">{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <h1 class="page-banner__title rise rise--d1">{{ $title }}</h1>
    </div>
</section>
