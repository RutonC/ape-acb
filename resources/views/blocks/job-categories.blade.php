@php
    $categories = \App\Models\JobCategory::query()->withCount('jobs')->orderBy('sort_order')->get();
@endphp

{{-- Procurar por categoria: cartões com ícone e número de vagas. --}}
@if ($categories->isNotEmpty())
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($categories as $category)
                <a href="{{ $category->url() }}" class="category-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                    <span class="category-card__icon"><x-dynamic-component :component="\App\Support\IconOptions::component($category->icon ?: 'briefcase')" class="size-6" /></span>
                    <span class="min-w-0">
                        <span class="category-card__name">{{ $category->getTranslation('name', app()->getLocale()) }}</span>
                        <span class="category-card__count">{{ trans_choice(':count vaga|:count vagas', $category->jobs_count) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
