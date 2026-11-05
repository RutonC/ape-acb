@php
    $whatsapp = preg_replace('/\D+/', '', (string) $company['whatsapp']);
    $phone = preg_replace('/\s+/', '', $company['phones'][0] ?? '');
    $message = rawurlencode(__('Olá, gostaria de saber mais sobre os serviços da APE.'));
@endphp

{{-- Contacto directo (RF07): WhatsApp, chamada em mobile e voltar ao topo --}}
<div x-data="scrollUp" class="fixed right-5 bottom-5 z-40 flex flex-col items-center gap-3 lg:right-10 lg:bottom-10">
    @if ($phone)
        <a href="tel:{{ $phone }}" class="inline-flex size-12 items-center justify-center rounded-full bg-brand text-white shadow-lg transition-transform hover:-translate-y-0.5 lg:hidden"
           aria-label="{{ __('Ligar para a APE') }}" data-track="phone-click">
            <x-heroicon-s-phone class="size-5" />
        </a>
    @endif

    @if ($whatsapp)
        <a href="https://wa.me/{{ $whatsapp }}?text={{ $message }}" target="_blank" rel="noopener"
           class="inline-flex size-12 items-center justify-center rounded-full bg-[#25d366] text-white shadow-lg transition-transform hover:-translate-y-0.5"
           aria-label="{{ __('Falar connosco no WhatsApp') }}" data-track="whatsapp-click">
            <x-social-icon name="whatsapp" class="size-6" />
        </a>
    @endif

    <button type="button" x-show="visible" x-cloak x-transition.opacity x-on:click="toTop" class="scroll-up" aria-label="{{ __('Voltar ao topo') }}">
        <x-heroicon-o-chevron-up class="size-5" />
    </button>
</div>
