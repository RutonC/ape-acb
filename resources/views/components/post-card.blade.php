@props(['post', 'compact' => false])

@php
    /** @var \App\Models\Post $post */
    $locale = app()->getLocale();
    $category = $post->category;
@endphp

{{-- Cartão de artigo (blog-item do tema): imagem, categoria, título, data, autor e resumo. --}}
<article {{ $attributes->merge(['class' => 'post-card']) }}>
    <div class="post-card__media">
        <a href="{{ $post->url() }}" tabindex="-1" aria-hidden="true">
            @if ($post->cover_image_id)
                <x-media :id="$post->cover_image_id" :widths="[1200, 800, 500]" :sizes="$compact ? '(min-width: 1024px) 33vw, 100vw' : '(min-width: 1024px) 60vw, 100vw'" class="post-card__image" />
            @else
                <div class="post-card__placeholder">
                    <img src="{{ asset('images/brand/logo-ape.png') }}" alt="" class="h-20 w-auto opacity-30">
                </div>
            @endif
        </a>
        @if ($category)
            <a href="{{ $category->url() }}" class="post-card__category">{{ $category->getTranslation('name', $locale) }}</a>
        @endif
    </div>

    <div class="post-card__body">
        <h3 @class(['post-card__title', 'post-card__title--compact' => $compact])>
            <a href="{{ $post->url() }}">{{ $post->getTranslation('title', $locale) }}</a>
        </h3>

        <ul class="post-meta">
            <li>
                <x-heroicon-o-calendar-days class="size-4" />
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ long_date($post->published_at) }}</time>
            </li>
            @unless ($compact)
                <li><x-heroicon-o-user class="size-4" /> {{ $post->authorName() }}</li>
                <li><x-heroicon-o-clock class="size-4" /> {{ trans_choice(':count min de leitura|:count min de leitura', $post->readingMinutes()) }}</li>
            @endunless
        </ul>

        @if (! $compact && filled($post->getTranslation('excerpt', $locale)))
            <p class="post-card__excerpt">{{ $post->getTranslation('excerpt', $locale) }}</p>
        @endif

        <a href="{{ $post->url() }}" class="card__link !pt-4">
            {{ __('Continuar a ler') }}
            <x-heroicon-o-arrow-long-right class="size-5" />
        </a>
    </div>
</article>
