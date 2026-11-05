@php
    $limit = (int) ($data['limit'] ?? 8);
    $jobs = \App\Models\JobOpening::query()->with('category')->openFirst()->limit($limit)->get();
    $hasOpen = $jobs->contains(fn ($job) => $job->isOpen());

    // Separadores por categoria: só as que têm vagas.
    $tabs = $jobs->pluck('category')->filter()->unique('id')->values();
@endphp

{{-- Vagas: separadores por categoria e cartões. Os separadores filtram sem recarregar. --}}
@if ($jobs->isNotEmpty())
    <div class="container-site" x-data="{ tab: null }">
        <x-section-heading :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        @if ($tabs->count() > 1)
            <div class="job-tabs" role="group" aria-label="{{ __('Filtrar por categoria') }}" data-aos="fade-up">
                <button type="button" class="job-tab" :class="{ 'is-active': tab === null }" :aria-pressed="tab === null" x-on:click="tab = null">{{ __('Todas') }}</button>
                @foreach ($tabs as $category)
                    <button type="button" class="job-tab" :class="{ 'is-active': tab === {{ $category->id }} }" :aria-pressed="tab === {{ $category->id }}" x-on:click="tab = {{ $category->id }}">
                        {{ $category->getTranslation('name', app()->getLocale()) }}
                    </button>
                @endforeach
            </div>
        @endif

        @unless ($hasOpen)
            <p class="job-notice" role="status">
                <x-heroicon-o-information-circle class="size-5 shrink-0" />
                <span>{{ __('De momento não há vagas abertas. As vagas abaixo já encerraram, mas pode enviar-nos o seu currículo.') }} <a href="{{ lroute('apply') }}">{{ __('Candidatura espontânea') }}</a></span>
            </p>
        @endunless

        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($jobs as $job)
                <div x-show="tab === null || tab === {{ (int) $job->job_category_id }}" class="flex">
                    <x-job-card :job="$job" class="w-full" />
                </div>
            @endforeach
        </div>

        @if (filled($data['button_label'] ?? null))
            <div class="mt-12 text-center">
                <a href="{{ lroute('jobs') }}" class="btn btn-primary">{{ $data['button_label'] }}</a>
            </div>
        @endif
    </div>
@endif
