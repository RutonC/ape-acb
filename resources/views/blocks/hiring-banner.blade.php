{{-- Faixa para empresas: pergunta, texto e botão. --}}
<div class="container-site">
    <div class="hiring-banner" data-aos="fade-up">
        <span class="hiring-banner__icon"><x-heroicon-o-building-office-2 class="size-9" /></span>
        <div class="hiring-banner__copy">
            <h2 class="hiring-banner__title">{{ $data['title'] ?? '' }}</h2>
            @if (filled($data['text'] ?? null))
                <p>{{ $data['text'] }}</p>
            @endif
        </div>
        <x-link-button :link="$data['button'] ?? null" :label="$data['button']['label'] ?? null" class="btn btn-primary shrink-0" />
    </div>
</div>
