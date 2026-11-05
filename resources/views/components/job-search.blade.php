@props(['categories', 'locations'])

{{-- Barra de pesquisa de vagas (topo da página inicial e da listagem). --}}
<form method="get" action="{{ lroute('jobs') }}" {{ $attributes->class(['job-search']) }} role="search">
    <div class="job-search__field">
        <x-heroicon-o-squares-2x2 class="job-search__icon" />
        <label for="pesquisa-categoria" class="sr-only">{{ __('Categoria') }}</label>
        <select id="pesquisa-categoria" name="categoria">
            <option value="">{{ __('Categoria') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected(request('categoria') === $category->slug)>{{ $category->getTranslation('name', app()->getLocale()) }}</option>
            @endforeach
        </select>
    </div>
    <div class="job-search__field">
        <x-heroicon-o-map-pin class="job-search__icon" />
        <label for="pesquisa-local" class="sr-only">{{ __('Local') }}</label>
        <select id="pesquisa-local" name="local">
            <option value="">{{ __('Local') }}</option>
            @foreach ($locations as $location)
                <option value="{{ $location['name'] }}" @selected(request('local') === $location['name'])>{{ $location['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="job-search__field job-search__field--wide">
        <x-heroicon-o-magnifying-glass class="job-search__icon" />
        <label for="pesquisa-q" class="sr-only">{{ __('Palavra-chave') }}</label>
        <input id="pesquisa-q" type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ __('Palavra-chave') }}">
    </div>
    <button type="submit" class="btn btn-primary job-search__button">
        <x-heroicon-o-magnifying-glass class="size-4" /> {{ __('Procurar') }}
    </button>
</form>
