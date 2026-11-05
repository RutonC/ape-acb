@php
    use App\Support\Localization;

    $phone = $company['phones'][0] ?? '';
    $social = $company['social'];
    $locales = config('site.locales');
    $headerMenu = $menu->items(\App\Enums\MenuLocation::Header);
@endphp

<header x-data="siteHeader" class="site-header" :class="{ 'is-scrolled': scrolled }">
    <div class="container-site flex h-20 items-center justify-between gap-6">
        <x-brand-logo :src="$company['logo']" :name="$company['name']" :href="lroute('home')" />

        {{-- Computador: menu ao centro --}}
        <nav aria-label="{{ __('Menu principal') }}" class="hidden xl:block">
            <ul class="main-menu flex items-center gap-1">
                @foreach ($headerMenu as $item)
                    <li @class(['has-children' => ! empty($item['children']), 'is-active' => $item['active']])>
                        <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>
                            {{ $item['label'] }}
                            @if (! empty($item['children']))
                                <x-heroicon-o-chevron-down class="size-3.5" aria-hidden="true" />
                            @endif
                        </a>

                        @if (! empty($item['children']))
                            <ul class="sub-menu">
                                @foreach ($item['children'] as $child)
                                    <li @class(['is-active' => $child['active']])>
                                        <a href="{{ $child['url'] }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif>
                                            {{ $child['label'] }}
                                            @if ($child['new_tab'])
                                                <x-heroicon-o-arrow-top-right-on-square class="size-3.5 shrink-0" aria-hidden="true" />
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2">
            @if (count($locales) > 1)
                <div class="lang-switch mr-2 hidden items-center gap-2 text-sm font-semibold uppercase xl:flex">
                    @foreach ($locales as $code => $name)
                        @if (! $loop->first)
                            <span class="opacity-50" aria-hidden="true">|</span>
                        @endif
                        <a href="{{ Localization::switchUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ $name }}" aria-current="{{ $code === app()->getLocale() ? 'true' : 'false' }}">{{ $code }}</a>
                    @endforeach
                </div>
            @endif

            <button type="button" x-on:click="toggleTheme" class="header-icon" :aria-pressed="dark" aria-label="{{ __('Alternar modo escuro') }}">
                <x-heroicon-o-moon class="size-5" x-show="! dark" />
                <x-heroicon-o-sun class="size-5" x-show="dark" x-cloak />
            </button>

            <a href="{{ lroute('companies') }}" class="hidden text-sm font-semibold text-heading underline underline-offset-4 hover:text-primary-500 2xl:inline">{{ __('Inscrever minha empresa') }}</a>
            <a href="{{ lroute('apply') }}" class="btn btn-primary btn-sm ml-2 hidden sm:inline-flex">{{ __('Candidate-se já') }}</a>

            <button type="button" x-on:click="openOffcanvas" class="header-icon xl:hidden" aria-label="{{ __('Abrir menu') }}" :aria-expanded="offcanvas">
                <x-heroicon-o-bars-3 class="size-6" />
            </button>
        </div>
    </div>

    {{-- Telemóvel e tablet: menu lateral --}}
    <div x-show="offcanvas" x-cloak class="fixed inset-0 z-50 xl:hidden" x-on:keydown.escape.window="closeOffcanvas">
        <div x-show="offcanvas" x-transition.opacity.duration.300ms x-on:click="closeOffcanvas" class="absolute inset-0 bg-dark-900/60"></div>

        <div x-show="offcanvas" x-trap.noscroll="offcanvas"
             x-transition:enter="offcanvas-panel" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="offcanvas-panel" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute inset-y-0 right-0 flex w-[320px] max-w-[88vw] flex-col overflow-y-auto bg-white px-6 pt-5 pb-8"
             role="dialog" aria-modal="true" aria-label="{{ __('Menu') }}">
            <div class="mb-6 flex items-center justify-between">
                <x-brand-logo :src="$company['logo']" :name="$company['name']" :href="lroute('home')" />
                <button type="button" x-on:click="closeOffcanvas" class="header-icon" aria-label="{{ __('Fechar menu') }}">
                    <x-heroicon-o-x-mark class="size-6" />
                </button>
            </div>

            <nav aria-label="{{ __('Menu principal') }}">
                <ul class="offcanvas-menu">
                    @foreach ($headerMenu as $item)
                        <li @class(['is-active' => $item['active']]) x-data="{ open: false }">
                            @if (! empty($item['children']))
                                <div class="flex items-center justify-between">
                                    <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                                    <button type="button" x-on:click="open = ! open" class="header-icon" :aria-expanded="open" aria-label="{{ __('Mostrar submenu') }}">
                                        <x-heroicon-o-chevron-down class="size-4 transition-transform duration-300" x-bind:class="{ 'rotate-180': open }" />
                                    </button>
                                </div>
                                <ul x-show="open" x-collapse class="pb-2 pl-3">
                                    @foreach ($item['children'] as $child)
                                        <li @class(['is-active' => $child['active']])>
                                            <a href="{{ $child['url'] }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif class="!py-2 !text-sm !font-medium">{{ $child['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <a href="{{ lroute('apply') }}" class="btn btn-primary mt-6 w-full">{{ __('Candidate-se já') }}</a>
            <a href="{{ lroute('companies') }}" class="btn btn-outline mt-3 w-full">{{ __('Inscrever minha empresa') }}</a>

            <ul class="mt-8 space-y-3 text-sm">
                <li class="flex gap-3">
                    <x-heroicon-o-map-pin class="mt-0.5 size-5 shrink-0 text-primary-500" />
                    <span>{{ $company['address'] }}</span>
                </li>
                @if ($company['email'])
                    <li class="flex gap-3">
                        <x-heroicon-o-envelope class="mt-0.5 size-5 shrink-0 text-primary-500" />
                        <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>
                    </li>
                @endif
                @if ($phone)
                    <li class="flex gap-3">
                        <x-heroicon-o-phone class="mt-0.5 size-5 shrink-0 text-primary-500" />
                        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
                    </li>
                @endif
            </ul>

            @if (count($locales) > 1)
                <div class="mt-6 flex items-center gap-2 text-sm font-semibold uppercase">
                    @foreach ($locales as $code => $name)
                        @if (! $loop->first)
                            <span class="text-border" aria-hidden="true">|</span>
                        @endif
                        <a href="{{ Localization::switchUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ $name }}" @class(['text-primary-500' => $code === app()->getLocale(), 'text-heading' => $code !== app()->getLocale()])>{{ $code }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</header>
