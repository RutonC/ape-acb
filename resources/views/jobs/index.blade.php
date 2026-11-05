@php
    $locale = app()->getLocale();
    $title = $page?->getTranslation('title', $locale) ?: __('Vagas');

    // Só texto: um endereço manipulado (?local[]=x) não pode provocar um erro.
    foreach (['q', 'categoria', 'local', 'tipo', 'estado'] as $key) {
        if (! is_string(request()->query($key))) {
            request()->query->remove($key);
        }
    }

    // Ligação para um filtro, mantendo os outros. Null retira o filtro.
    $filter = fn (string $key, ?string $value) => lroute('jobs').(($query = http_build_query(array_filter([...request()->query->all(), 'page' => null, $key => $value], fn ($item) => filled($item)))) ? '?'.$query : '');

    $hasFilters = collect(['q', 'categoria', 'local', 'tipo', 'estado'])->contains(fn (string $key) => request()->filled($key));
@endphp

<x-layouts.site
    :title="$title"
    :full-title="$page?->getTranslation('meta_title', $locale) ?: null"
    :image="$page?->og_image_id"
    :noindex="$hasFilters"
    :description="$page?->getTranslation('meta_description', $locale) ?: __('Vagas de emprego e estágio publicadas pela Agência Privada de Emprego, na Beira.')"
>
    <div class="container-site pt-6">
        <section class="jobs-banner">
            <h1 class="jobs-banner__title">
                @if ($openCount > 0)
                    <span class="job-hero__highlight">{{ trans_choice(':count vaga|:count vagas', $openCount) }}</span> {{ trans_choice('disponível agora|disponíveis agora', $openCount) }}
                @else
                    {{ __('Vagas de emprego') }}
                @endif
            </h1>
            <p class="jobs-banner__text">{{ __('Procure por categoria, local ou palavra-chave.') }}</p>

            <x-job-search :categories="$categories" :locations="$locations" class="mx-auto mt-7 max-w-[860px]" />
        </section>
    </div>

    <section class="block block--white py-12 lg:py-16" data-block="jobs">
        <div class="container-site grid gap-10 lg:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="jobs-filters" aria-label="{{ __('Filtros') }}" x-data="{ open: false }">
                <button type="button" class="btn btn-outline w-full lg:hidden" x-on:click="open = ! open" :aria-expanded="open">
                    <x-heroicon-o-adjustments-horizontal class="size-5" /> {{ __('Filtros') }}
                </button>

                <div class="hidden lg:block" :class="{ '!block mt-5': open }">
                    <div class="jobs-filters__head">
                        <h2>{{ __('Filtros') }}</h2>
                        @if ($hasFilters)
                            <a href="{{ lroute('jobs') }}">{{ __('Limpar') }}</a>
                        @endif
                    </div>

                    <h3 class="jobs-filters__title">{{ __('Estado') }}</h3>
                    <ul class="jobs-filters__list">
                        @foreach ([[null, __('Todas'), $openCount + $closedCount], ['abertas', __('Abertas'), $openCount], ['encerradas', __('Encerradas'), $closedCount]] as [$value, $label, $count])
                            <li><a href="{{ $filter('estado', $value) }}" @class(['is-active' => request('estado') === $value])><span class="jobs-filters__check"></span>{{ $label }}<span class="jobs-filters__count">{{ $count }}</span></a></li>
                        @endforeach
                    </ul>

                    <h3 class="jobs-filters__title">{{ __('Categoria') }}</h3>
                    <ul class="jobs-filters__list">
                        <li><a href="{{ $filter('categoria', null) }}" @class(['is-active' => ! request()->filled('categoria')])><span class="jobs-filters__check"></span>{{ __('Todas') }}</a></li>
                        @foreach ($categories as $category)
                            <li><a href="{{ $filter('categoria', $category->slug) }}" @class(['is-active' => request('categoria') === $category->slug])><span class="jobs-filters__check"></span>{{ $category->getTranslation('name', $locale) }}<span class="jobs-filters__count">{{ $category->jobs_count }}</span></a></li>
                        @endforeach
                    </ul>

                    @if ($locations->isNotEmpty())
                        <h3 class="jobs-filters__title">{{ __('Local') }}</h3>
                        <ul class="jobs-filters__list">
                            <li><a href="{{ $filter('local', null) }}" @class(['is-active' => ! request()->filled('local')])><span class="jobs-filters__check"></span>{{ __('Todos') }}</a></li>
                            @foreach ($locations as $location)
                                <li><a href="{{ $filter('local', $location['name']) }}" @class(['is-active' => request('local') === $location['name']])><span class="jobs-filters__check"></span>{{ $location['name'] }}<span class="jobs-filters__count">{{ $location['total'] }}</span></a></li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($contracts->isNotEmpty())
                        <h3 class="jobs-filters__title">{{ __('Tipo de contrato') }}</h3>
                        <ul class="jobs-filters__list">
                            <li><a href="{{ $filter('tipo', null) }}" @class(['is-active' => ! request()->filled('tipo')])><span class="jobs-filters__check"></span>{{ __('Todos') }}</a></li>
                            @foreach ($contracts as $contract)
                                <li><a href="{{ $filter('tipo', $contract) }}" @class(['is-active' => request('tipo') === $contract])><span class="jobs-filters__check"></span>{{ $contract }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </aside>

            <div>
                <div class="jobs-toolbar">
                    <p role="status">
                        @if ($jobs->total() > 0)
                            {{ __('A mostrar :from a :to de :total', ['from' => $jobs->firstItem(), 'to' => $jobs->lastItem(), 'total' => $jobs->total()]) }}
                        @else
                            {{ __('Nenhuma vaga encontrada') }}
                        @endif
                    </p>
                    <div class="jobs-toolbar__views" role="group" aria-label="{{ __('Forma de apresentação') }}">
                        <a href="{{ $filter('vista', null) }}" @class(['is-active' => $layout === 'grid']) aria-label="{{ __('Ver em grelha') }}" @if ($layout === 'grid') aria-current="true" @endif><x-heroicon-o-squares-2x2 class="size-5" /></a>
                        <a href="{{ $filter('vista', 'lista') }}" @class(['is-active' => $layout === 'list']) aria-label="{{ __('Ver em lista') }}" @if ($layout === 'list') aria-current="true" @endif><x-heroicon-o-bars-3 class="size-5" /></a>
                    </div>
                </div>

                @if ($openCount === 0 && $jobs->total() > 0)
                    <p class="job-notice" role="status">
                        <x-heroicon-o-information-circle class="size-5 shrink-0" />
                        <span>{{ __('De momento não há vagas abertas. Pode enviar-nos o seu currículo.') }} <a href="{{ lroute('apply') }}">{{ __('Candidatura espontânea') }}</a></span>
                    </p>
                @endif

                @if ($jobs->isEmpty())
                    <div class="jobs-empty">
                        <x-heroicon-o-magnifying-glass class="mx-auto mb-4 size-10 text-primary-300" />
                        <p class="mb-5">{{ __('Experimente outros filtros ou envie-nos o seu currículo.') }}</p>
                        <a href="{{ lroute('jobs') }}" class="btn btn-outline">{{ __('Ver todas as vagas') }}</a>
                        <a href="{{ lroute('apply') }}" class="btn btn-primary">{{ __('Candidatura espontânea') }}</a>
                    </div>
                @else
                    <div @class(['grid gap-6', 'sm:grid-cols-2 xl:grid-cols-3' => $layout === 'grid'])>
                        @foreach ($jobs as $job)
                            <x-job-card :job="$job" :layout="$layout" />
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $jobs->links('partials.site.pagination') }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    <x-blocks :blocks="$blocks" />
</x-layouts.site>
