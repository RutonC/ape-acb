@php
    $subjects = ['' => __('Seleccione...')] + collect(config('site.contact_subjects'))->map(fn (string $label) => __($label))->all();
    $anchor = $data['anchor'] ?? null ?: 'pedido-de-contacto';
@endphp

{{-- Pedido de contacto rápido: nome, telefone, e-mail e assunto; fica na Caixa de entrada. --}}
<div class="container-site grid items-start gap-10 lg:grid-cols-[5fr_7fr] lg:gap-16">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" align="left" class="!mb-0" />

    <div id="{{ $anchor }}" class="form-card scroll-mt-28" data-aos="fade-up">
        @if (session('contact_sent'))
            <div class="form-success" role="status" data-analytics-event="generate_lead" data-analytics-form="pedido-de-contacto">
                <x-heroicon-o-check-circle class="size-7 shrink-0" />
                <p>{{ __('Obrigado pelo seu contacto. A sua mensagem foi recebida e entraremos em contacto consigo.') }}</p>
            </div>
        @endif

        <form method="post" action="{{ lroute('contact.store') }}" class="grid gap-5 sm:grid-cols-2" novalidate data-track-form="contact-request">
            @csrf
            <input type="hidden" name="quick" value="1">
            <input type="hidden" name="return_to" value="#{{ $anchor }}">

            <x-form-field name="name" :label="__('Nome')" required autocomplete="name" class="sm:col-span-2" />
            <x-form-field name="phone" type="tel" :label="__('Telefone')" required autocomplete="tel" inputmode="tel" />
            <x-form-field name="email" type="email" :label="__('E-mail')" required autocomplete="email" />
            <x-form-field name="service" type="select" :label="__('Assunto')" :options="$subjects" class="sm:col-span-2" />

            {{-- Armadilha para robôs: invisível para pessoas --}}
            <div class="hidden" aria-hidden="true">
                <label for="pedido-website">Website</label>
                <input id="pedido-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm">
                    {{ __('Os seus dados são usados apenas para responder ao contacto.') }}
                    <a href="{{ lroute('privacy') }}" class="text-primary-600 underline underline-offset-2">{{ __('Política de Privacidade') }}</a>
                </p>
                <button type="submit" class="btn btn-primary shrink-0">{{ __('Enviar pedido') }}</button>
            </div>
        </form>
    </div>
</div>
