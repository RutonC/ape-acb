@php
    /** @var \App\Models\JobOpening|null $opening */
    $opening ??= null;
    $openings ??= null;
    $locale = app()->getLocale();
    $toOptions = fn (array $values) => array_combine($values, $values);
@endphp

{{-- Formulário de candidatura, com os campos do site antigo. O CV fica em disco privado. --}}
<div id="candidatura" class="form-card scroll-mt-28">
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">
        {{ $opening ? __('Candidatar-me a esta vaga') : __('Faça já a sua candidatura') }}
    </h2>
    @if ($opening)
        <p class="mb-8">{{ __('Está a candidatar-se à vaga:') }} <strong>{{ $opening->getTranslation('title', $locale) }}</strong></p>
    @else
        <p class="mb-8">{{ __('Escolha uma vaga aberta ou envie uma candidatura espontânea.') }}</p>
    @endif

    @if (session('application_sent'))
        <div class="form-success" role="status" data-analytics-event="job_application" data-analytics-form="candidatura">
            <x-heroicon-o-check-circle class="size-7 shrink-0" />
            <p>{{ __('Obrigado pela sua candidatura. Recebemos o seu currículo e entraremos em contacto se o seu perfil corresponder a uma vaga.') }}</p>
        </div>
    @endif

    <form method="post" action="{{ lroute('apply.store') }}" enctype="multipart/form-data" class="grid gap-5 sm:grid-cols-6" novalidate data-track-form="application">
        @csrf

        <x-form-field name="name" :label="__('Nome completo')" required autocomplete="name" :placeholder="__('Escreva o seu nome e sobrenome')" class="sm:col-span-6" />
        <x-form-field name="birth_date" type="date" :label="__('Data de nascimento')" required autocomplete="bday" class="sm:col-span-3" />
        <x-form-field name="gender" type="select" :label="__('Sexo')" required :options="$toOptions($genders ?? \App\Models\JobApplication::GENDERS)" class="sm:col-span-3" />
        <x-form-field name="document_type" type="select" :label="__('Documento de registo')" :options="$toOptions($documentTypes ?? \App\Models\JobApplication::DOCUMENT_TYPES)" class="sm:col-span-3" />
        <x-form-field name="document_number" :label="__('Número do documento de registo')" autocomplete="off" class="sm:col-span-3" />

        @if ($opening)
            <input type="hidden" name="job_opening_id" value="{{ $opening->id }}">
        @elseif ($openings !== null && $openings->isNotEmpty())
            <x-form-field name="job_opening_id" type="select" :label="__('Vaga a que se candidata')" :placeholder="__('Candidatura espontânea')"
                :options="$openings->mapWithKeys(fn ($job) => [$job->id => $job->getTranslation('title', $locale)])->all()" class="sm:col-span-6" />
        @endif

        <x-form-field name="qualifications" :label="__('Habilitações literárias')" class="sm:col-span-4" />
        <x-form-field name="experience_years" type="number" :label="__('Anos de experiência')" inputmode="numeric" class="sm:col-span-2" />
        <x-form-field name="address" :label="__('Endereço')" required autocomplete="street-address" :placeholder="__('Quarteirão, rua, cidade, país')" class="sm:col-span-6" />
        <x-form-field name="email" type="email" :label="__('E-mail')" required autocomplete="email" class="sm:col-span-2" />
        <x-form-field name="phone" type="tel" :label="__('Telefone')" required autocomplete="tel" inputmode="tel" class="sm:col-span-2" />
        <x-form-field name="phone_alt" type="tel" :label="__('Telefone alternativo')" inputmode="tel" class="sm:col-span-2" />
        <x-form-field name="cv" type="file" :label="__('Currículo (PDF ou Word, até 20 MB)')" required accept=".pdf,.doc,.docx,application/pdf" :hint="__('O seu currículo fica num arquivo privado, sem acesso público.')" class="sm:col-span-6" />
        <x-form-field name="message" type="textarea" :rows="4" :label="__('Observações')" class="sm:col-span-6" />

        <div class="hidden" aria-hidden="true">
            <label for="campo-website-candidatura">Website</label>
            <input id="campo-website-candidatura" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-field sm:col-span-6">
            <label class="flex items-start gap-3 text-[15px]">
                <input type="checkbox" name="send_copy" value="1" @checked(old('send_copy')) class="mt-1 size-4 accent-primary-500">
                <span>{{ __('Quero receber um e-mail de confirmação.') }}</span>
            </label>
        </div>

        <div @class(['form-field sm:col-span-6', 'has-error' => $errors->has('consent')])>
            <label class="flex items-start gap-3 text-[15px]">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-1 size-4 accent-primary-500">
                <span>
                    {{ __('Autorizo a APE a tratar os meus dados para efeitos de recrutamento, nos termos da') }}
                    <a href="{{ lroute('privacy') }}" class="text-primary-600 underline underline-offset-2">{{ __('Política de Privacidade') }}</a>.
                </span>
            </label>
            @error('consent')
                <p class="form-field__error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-6">
            <button type="submit" class="btn btn-primary">{{ __('Candidatar-me') }}</button>
        </div>
    </form>
</div>
