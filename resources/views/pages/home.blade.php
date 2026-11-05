{{-- Página inicial provisória. A página Início completa chega na Fase 2. --}}
<x-layouts.site :description="$company['about']">
    <section class="page-banner !py-[140px] text-white lg:!py-[180px]">
        <div class="container-site relative">
            <div class="max-w-[720px]">
                <h1 class="mb-6 text-[clamp(34px,5vw,52px)] leading-[1.3] font-bold !text-white" data-aos="fade-up">
                    {{ $company['name'] }}
                </h1>
                <p class="mb-10 text-lg leading-[1.9] text-white/85" data-aos="fade-up" data-aos-delay="100">
                    {{ $company['about'] }}
                </p>
                <div class="flex flex-wrap gap-4" data-aos="fade-up" data-aos-delay="200">
                    <a href="{{ lroute('contact') }}" class="btn btn-primary">{{ __('Fale connosco') }}</a>
                    <a href="{{ lroute('services') }}" class="btn btn-white">{{ __('Ver serviços') }}</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
