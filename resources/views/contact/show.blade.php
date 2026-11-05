@php
    $locale = app()->getLocale();
    $title = $page?->getTranslation('title', $locale) ?: __('Contactos');
@endphp

<x-layouts.site
    :title="$title"
    :full-title="$page?->getTranslation('meta_title', $locale) ?: null"
    :image="$page?->og_image_id"
    :description="$page?->getTranslation('meta_description', $locale) ?: __('Telefones, e-mail, localização e formulário de contacto da Agência Privada de Emprego, na Beira.')"
>
    <x-page-banner :title="$title" :breadcrumbs="[['label' => $title]]" />

    <section class="block block--white pt-12 lg:pt-16" data-block="contact-card">
        <div class="container-site">
            <div class="contact-panel">
                <div>
                    <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="contact-panel__logo" width="120" height="80" loading="lazy">
                </div>
                <div>
                    <h2>{{ __('Telefones') }}</h2>
                    @foreach ($company['phones'] as $number)
                        <a href="tel:{{ preg_replace('/\s+/', '', $number) }}" data-track="phone-click">{{ $number }}</a>
                    @endforeach
                </div>
                <div>
                    <h2>{{ __('E-mail') }}</h2>
                    @foreach (array_unique(array_filter([$company['forms_recipient_email'], $company['email']])) as $address)
                        <a href="mailto:{{ $address }}" class="break-all">{{ $address }}</a>
                    @endforeach
                </div>
                <div>
                    <h2>{{ __('Onde estamos') }}</h2>
                    <span>{{ $company['address'] }}</span>
                    @if ($company['hours'])
                        <span>{{ $company['hours'] }}</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="block block--white block--padded" data-block="contact">
        <div class="container-site grid items-start gap-12 lg:grid-cols-2">
            <div id="formulario" class="scroll-mt-28">
                <h2 class="mb-3 text-3xl font-bold">{{ __('Envie-nos um e-mail') }}</h2>
                <p class="mb-8">{{ __('Escreva-nos e a nossa equipa entrará em contacto consigo.') }}</p>

                @if (session('contact_sent'))
                    <div class="form-success" role="status" data-analytics-event="generate_lead" data-analytics-form="contacto">
                        <x-heroicon-o-check-circle class="size-7 shrink-0" />
                        <p>{{ __('Obrigado pelo seu contacto. A sua mensagem foi recebida e entraremos em contacto consigo.') }}</p>
                    </div>
                @endif

                <form method="post" action="{{ lroute('contact.store') }}" class="grid gap-5 sm:grid-cols-2" novalidate data-track-form="contact">
                    @csrf
                    <x-form-field name="name" :label="__('Nome completo')" required autocomplete="name" />
                    <x-form-field name="email" type="email" :label="__('E-mail')" required autocomplete="email" />
                    <x-form-field name="subject" :label="__('Assunto')" required class="sm:col-span-2" />
                    <x-form-field name="message" type="textarea" :label="__('Mensagem')" required :placeholder="__('Escreva a sua mensagem…')" class="sm:col-span-2" />

                    {{-- Armadilha para robôs: invisível para pessoas --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="campo-website">Website</label>
                        <input id="campo-website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <label class="flex items-start gap-3 text-[15px] sm:col-span-2">
                        <input type="checkbox" name="send_copy" value="1" @checked(old('send_copy')) class="mt-1 size-4 accent-primary-500">
                        <span>{{ __('Quero receber um e-mail de confirmação.') }}</span>
                    </label>

                    <div class="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm">
                            {{ __('Os seus dados são usados apenas para responder ao contacto.') }}
                            <a href="{{ lroute('privacy') }}" class="text-primary-600 underline underline-offset-2">{{ __('Política de Privacidade') }}</a>
                        </p>
                        <button type="submit" class="btn btn-primary shrink-0">{{ __('Enviar mensagem') }}</button>
                    </div>
                </form>
            </div>

            {{-- Mapa: só carrega o Google Maps com consentimento de cookies --}}
            <div class="contact-map rounded-card overflow-hidden" x-data="{ show: window.acbConsent?.() === 'accepted' }" x-on:consent-updated.window="show = $event.detail === 'accepted' || show" aria-label="{{ __('Mapa') }}">
                <template x-if="show">
                    <iframe src="{{ $company['map_embed_url'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ __('Localização da APE no mapa') }}"></iframe>
                </template>
                <div x-show="! show" class="contact-map__placeholder">
                    <x-heroicon-o-map class="size-12 text-primary-300" />
                    <p>{{ __('Encontre-nos aqui!') }}</p>
                    <button type="button" class="btn btn-white btn-sm" x-on:click="show = true">{{ __('Mostrar mapa (Google Maps)') }}</button>
                </div>
            </div>
        </div>
    </section>

    <x-blocks :blocks="$blocks" />
</x-layouts.site>
