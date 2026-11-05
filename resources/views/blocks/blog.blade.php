@php
    $posts = \App\Models\Post::published()
        ->with('category')
        ->when(filled($data['post_category_id'] ?? null), fn ($query) => $query->where('post_category_id', $data['post_category_id']))
        ->latestFirst()
        ->limit((int) ($data['limit'] ?? 3))
        ->get();
@endphp

{{-- Últimos artigos (rsblog-slider do tema, em grelha) --}}
@if ($posts->isNotEmpty())
    <div class="container-site">
        <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        <div class="grid gap-[30px] md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <x-post-card :post="$post" compact data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 100 }}" />
            @endforeach
        </div>

        @if (filled($data['button_label'] ?? null))
            <div class="mt-12 text-center" data-aos="fade-up">
                <a href="{{ lroute('blog') }}" class="btn btn-primary">{{ $data['button_label'] }}</a>
            </div>
        @endif
    </div>
@endif
