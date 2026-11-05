<x-layouts.site :title="$title">
    <x-page-banner :title="$title" :breadcrumbs="$breadcrumbs" />

    <section class="py-[100px]">
        <div class="container-site max-w-3xl text-center" data-aos="fade-up">
            <x-heroicon-o-wrench-screwdriver class="mx-auto mb-6 size-12 text-primary-500" />
            <h2 class="mb-4 text-3xl font-bold">{{ __('Página em preparação') }}</h2>
            <p class="mb-8 text-lg">
                {{ __('Estamos a preparar o conteúdo desta página. Entretanto, fale connosco.') }}
            </p>
            <a href="{{ lroute('contact') }}" class="btn btn-primary">{{ __('Falar com a APE') }}</a>
        </div>
    </section>
</x-layouts.site>
