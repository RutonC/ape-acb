{{-- Aviso de cookies (RF08): cookies analíticos só depois de aceitação explícita --}}
<div x-data="cookieConsent" x-show="open" x-cloak
     x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-y-6 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition duration-300 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-x-3 bottom-3 z-[60] sm:inset-x-auto sm:left-6 sm:bottom-6 sm:max-w-[430px]"
     role="dialog" aria-live="polite" aria-label="{{ __('Aviso de cookies') }}">
    <div class="rounded-card border border-border bg-white p-6 shadow-card">
        <p class="mb-1 font-heading text-lg font-semibold text-heading">{{ __('Utilizamos cookies') }}</p>
        <p class="mb-5 text-[15px] leading-relaxed">
            {{ __('Usamos cookies essenciais para o funcionamento do site e, com a sua autorização, cookies analíticos para melhorar a experiência.') }}
            <a href="{{ lroute('privacy') }}" class="font-medium text-primary-600 underline underline-offset-2">{{ __('Política de Privacidade') }}</a>
        </p>
        <div class="flex flex-wrap gap-3">
            <button type="button" x-on:click="accept" class="btn btn-primary btn-sm">{{ __('Aceitar') }}</button>
            <button type="button" x-on:click="reject" class="btn btn-sm border border-border text-heading transition-colors hover:border-primary-500 hover:text-primary-500">{{ __('Recusar') }}</button>
        </div>
    </div>
</div>
