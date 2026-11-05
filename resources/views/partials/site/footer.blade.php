@php
    $social = $company['social'];
@endphp

<footer class="site-footer">
    <div class="container-site grid gap-10 pt-14 pb-10 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.3fr] lg:gap-8">
        <div>
            <a href="{{ lroute('home') }}" class="mb-5 inline-block" aria-label="{{ $company['name'] }}">
                <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="site-footer__logo" width="120" height="80" loading="lazy">
            </a>
            <p class="mb-6 max-w-[340px] text-sm">{{ $company['about'] }}</p>

            @if ($social)
                <ul class="footer-social flex gap-2">
                    @foreach ($social as $network => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">
                                <x-social-icon :name="$network" class="size-4" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <h2 class="footer-title">{{ __('Navegação') }}</h2>
            <ul class="footer-links">
                @foreach ($menu->items(\App\Enums\MenuLocation::Footer) as $item)
                    <li><a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="footer-title">{{ __('Serviços') }}</h2>
            <ul class="footer-links">
                @foreach ($menu->items(\App\Enums\MenuLocation::FooterServices) as $item)
                    <li><a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="footer-title">{{ __('Contactos') }}</h2>
            <ul class="footer-contact">
                <li>
                    <x-heroicon-o-map-pin class="footer-contact__icon" />
                    <span>{{ $company['address'] }}</span>
                </li>
                @foreach ($company['phones'] as $number)
                    <li>
                        <x-heroicon-o-phone class="footer-contact__icon" />
                        <a href="tel:{{ preg_replace('/\s+/', '', $number) }}">{{ $number }}</a>
                    </li>
                @endforeach
                @if ($company['email'])
                    <li>
                        <x-heroicon-o-envelope class="footer-contact__icon" />
                        <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>
                    </li>
                @endif
                @if ($company['hours'])
                    <li>
                        <x-heroicon-o-clock class="footer-contact__icon" />
                        <span>{{ $company['hours'] }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="container-site">
        <div class="footer-bottom">
            <p>&copy; {{ now()->year }} {{ __('Associação Comercial da Beira. Todos os direitos reservados.') }}</p>

            <ul class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1">
                @foreach ($menu->items(\App\Enums\MenuLocation::Legal) as $item)
                    <li><a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                @endforeach
                <li>
                    <button type="button" x-data x-on:click="$dispatch('open-cookie-consent')">{{ __('Preferências de cookies') }}</button>
                </li>
            </ul>
        </div>
    </div>
</footer>
