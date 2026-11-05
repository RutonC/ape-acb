@php
    $locale = app()->getLocale();
@endphp

{{-- Barra lateral do blog (widgets do tema): pesquisa, recentes, categorias e etiquetas. --}}
<aside class="space-y-8">
    <div class="blog-widget">
        <form action="{{ lroute('blog') }}" method="get" role="search" class="relative">
            <label for="blog-search" class="sr-only">{{ __('Pesquisar nas notícias') }}</label>
            <input id="blog-search" type="search" name="q" value="{{ $search ?? '' }}" placeholder="{{ __('Pesquisar…') }}" class="blog-search__input">
            <button type="submit" class="blog-search__button" aria-label="{{ __('Pesquisar') }}">
                <x-heroicon-o-magnifying-glass class="size-5" />
            </button>
        </form>
    </div>

    @if ($recentPosts->isNotEmpty())
        <div class="blog-widget">
            <h2 class="blog-widget__title">{{ __('Notícias recentes') }}</h2>
            <ul class="space-y-5">
                @foreach ($recentPosts as $recent)
                    <li class="flex gap-4">
                        <a href="{{ $recent->url() }}" class="size-20 shrink-0 overflow-hidden rounded-card" tabindex="-1" aria-hidden="true">
                            @if ($recent->cover_image_id)
                                <x-media :id="$recent->cover_image_id" :widths="[200]" sizes="80px" class="size-full object-cover" />
                            @else
                                <span class="post-card__placeholder !rounded-none"><x-heroicon-o-document-text class="size-7 text-white/60" /></span>
                            @endif
                        </a>
                        <div>
                            <a href="{{ $recent->url() }}" class="font-heading leading-snug font-semibold text-heading transition-colors hover:text-primary-500">{{ $recent->getTranslation('title', $locale) }}</a>
                            <p class="mt-1 text-sm">{{ long_date($recent->published_at) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($categories->isNotEmpty())
        <div class="blog-widget">
            <h2 class="blog-widget__title">{{ __('Categorias') }}</h2>
            <ul class="blog-categories">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ $category->url() }}">
                            <span>{{ $category->getTranslation('name', $locale) }}</span>
                            <span class="blog-categories__count">{{ $category->posts_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($tags->isNotEmpty())
        <div class="blog-widget">
            <h2 class="blog-widget__title">{{ __('Etiquetas') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($tags as $tag)
                    <a href="{{ $tag->url() }}" class="blog-tag">{{ $tag->getTranslation('name', $locale) }}</a>
                @endforeach
            </div>
        </div>
    @endif
</aside>
