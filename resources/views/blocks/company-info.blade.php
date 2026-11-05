@php
    $rows = array_filter([
        ['heroicon-o-building-office-2', __('Designação'), $company['name']],
        ['heroicon-o-map-pin', __('Sede'), $company['address']],
        ['heroicon-o-identification', 'NUIT', $company['nuit']],
        ['heroicon-o-document-text', __('Registo comercial'), $company['commercial_registry']],
        ['heroicon-o-phone', __('Telefones'), implode(' | ', $company['phones'])],
        ['heroicon-o-envelope', __('E-mail'), $company['email']],
    ], fn (array $row) => filled($row[2]));
@endphp

<div class="container-site max-w-[1000px]">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <ul class="grid gap-5 sm:grid-cols-2" role="list" data-aos="fade-up">
        @foreach ($rows as [$icon, $label, $value])
            <li class="contact-box">
                <span class="contact-box__icon" aria-hidden="true"><x-dynamic-component :component="$icon" class="size-6" /></span>
                <div>
                    <p class="contact-box__label">{{ $label }}</p>
                    <p class="contact-box__text">{{ $value }}</p>
                </div>
            </li>
        @endforeach
    </ul>
</div>
