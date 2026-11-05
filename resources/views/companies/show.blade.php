@php
    $locale = app()->getLocale();
    $title = $page?->getTranslation('title', $locale) ?: __('Inscrever minha empresa');
    $recruitment = \App\Models\Service::published()->where('slug', 'recrutamento-e-seleccao')->first();
@endphp

<x-layouts.site
    :title="$title"
    :full-title="$page?->getTranslation('meta_title', $locale) ?: null"
    :image="$page?->og_image_id"
    :description="$page?->getTranslation('meta_description', $locale) ?: __('Inscreva a sua empresa na Agência Privada de Emprego: seleccionamos e identificamos o pessoal certo para as suas áreas de actividade.')"
>
    <x-page-banner :title="$title" :breadcrumbs="[['label' => $title]]" />

    <section class="block block--white block--padded" data-block="companies">
        <div class="container-site grid items-start gap-12 lg:grid-cols-2">
            <div>
                <h2 class="mb-4 text-2xl font-bold sm:text-3xl">{{ __('Pretende recrutar?') }}</h2>
                <p class="mb-8 text-lg">{{ __('Seleccionamos e identificamos o pessoal certo para as áreas de actividade da sua empresa.') }}</p>

                @foreach ($recruitment?->detailSections() ?? [] as $section)
                    <h3 class="mb-3 text-lg font-bold">{{ $section['title'] ?? '' }}</h3>
                    <x-rich-content :content="$section['content'] ?? null" class="mb-8" />
                @endforeach

                <a href="{{ lroute('services') }}" class="btn btn-outline">{{ __('Conhecer os serviços') }}</a>
            </div>

            <div id="inscricao" class="form-card scroll-mt-28">
                <h2 class="mb-3 text-2xl font-bold">{{ __('Inscreva a sua empresa') }}</h2>
                <p class="mb-8">{{ __('Preencha o formulário para que possamos entrar em contacto e registar a sua empresa.') }}</p>

                @if (session('company_sent'))
                    <div class="form-success" role="status" data-analytics-event="generate_lead" data-analytics-form="empresa">
                        <x-heroicon-o-check-circle class="size-7 shrink-0" />
                        <p>{{ __('Obrigado. Recebemos a inscrição da sua empresa e entraremos em contacto consigo.') }}</p>
                    </div>
                @endif

                <form method="post" action="{{ lroute('companies.store') }}" class="grid gap-5" novalidate data-track-form="company">
                    @csrf
                    <x-form-field name="company_name" :label="__('Nome da empresa')" required autocomplete="organization" />
                    <x-form-field name="name" :label="__('Nome')" required autocomplete="name" />
                    <x-form-field name="phone" type="tel" :label="__('Telefone')" required autocomplete="tel" inputmode="tel" placeholder="+258 84 000 0000" />
                    <x-form-field name="email" type="email" :label="__('E-mail')" required autocomplete="email" />

                    <div class="hidden" aria-hidden="true">
                        <label for="campo-website-empresa">Website</label>
                        <input id="campo-website-empresa" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <label class="flex items-start gap-3 text-[15px]">
                        <input type="checkbox" name="send_copy" value="1" @checked(old('send_copy')) class="mt-1 size-4 accent-primary-500">
                        <span>{{ __('Quero receber um e-mail de confirmação.') }}</span>
                    </label>

                    <p class="text-sm">
                        {{ __('Os seus dados são usados apenas para contactar a sua empresa.') }}
                        <a href="{{ lroute('privacy') }}" class="text-primary-600 underline underline-offset-2">{{ __('Política de Privacidade') }}</a>
                    </p>

                    <div><button type="submit" class="btn btn-primary">{{ __('Enviar') }}</button></div>
                </form>
            </div>
        </div>
    </section>

    <x-blocks :blocks="$blocks" />
</x-layouts.site>
