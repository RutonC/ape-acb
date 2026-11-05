@php
    /** @var \App\Models\Post $post */
    $locale = app()->getLocale();
    $title = $post->getTranslation('title', $locale);
    $shareUrl = rawurlencode($post->url());
    $shareText = rawurlencode($title);
    $shares = [
        'facebook' => ['Facebook', "https://www.facebook.com/sharer/sharer.php?u={$shareUrl}"],
        'linkedin' => ['LinkedIn', "https://www.linkedin.com/sharing/share-offsite/?url={$shareUrl}"],
        'x' => ['X', "https://twitter.com/intent/tweet?url={$shareUrl}&text={$shareText}"],
        'whatsapp' => ['WhatsApp', "https://wa.me/?text={$shareText}%20{$shareUrl}"],
    ];
    $breadcrumbs = array_values(array_filter([
        ['label' => __('Notícias'), 'url' => lroute('blog')],
        $post->category ? ['label' => $post->category->getTranslation('name', $locale), 'url' => $post->category->url()] : null,
        ['label' => $title],
    ]));
@endphp

<x-layouts.site
    :title="$title"
    :full-title="$post->getTranslation('meta_title', $locale) ?: null"
    :description="$post->getTranslation('meta_description', $locale) ?: $post->getTranslation('excerpt', $locale)"
    :noindex="$preview"
    :image="$post->cover_image_id"
    type="article"
    :published-at="$preview ? null : $post->published_at"
>
    @if ($preview)
        <div class="bg-amber-400 px-4 py-2 text-center text-sm font-semibold text-dark-900">
            {{ __('Pré-visualização') }}: {{ $post->isVisible() ? __('Publicado') : ($post->published_at?->isFuture() ? __('Agendado para :data', ['data' => $post->published_at->translatedFormat('j F Y, H:i')]) : __('Rascunho')) }}
        </div>
    @else
        @push('structured-data')
            {!! \App\Support\StructuredData::toScript(\App\Support\StructuredData::article($post)) !!}
        @endpush
    @endif

    <x-page-banner :title="$title" :breadcrumbs="$breadcrumbs" />

    <section class="block block--light block--padded" data-block="post">
        <div class="container-site grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <article class="post-card post-card--single">
                <div class="post-card__media">
                    @if ($post->cover_image_id)
                        <x-media :id="$post->cover_image_id" :widths="[1600, 1200, 800]" sizes="(min-width: 1024px) 60vw, 100vw" loading="eager" class="post-card__image" />
                    @else
                        <div class="post-card__placeholder aspect-[16/7]">
                            <img src="{{ asset('images/brand/logo.svg') }}" alt="" class="h-24 w-auto opacity-30">
                        </div>
                    @endif
                    @if ($post->category)
                        <a href="{{ $post->category->url() }}" class="post-card__category">{{ $post->category->getTranslation('name', $locale) }}</a>
                    @endif
                </div>

                <div class="post-card__body">
                    <ul class="post-meta">
                        <li>
                            <x-heroicon-o-calendar-days class="size-4" />
                            <time datetime="{{ $post->published_at?->toDateString() }}">{{ long_date($post->published_at) }}</time>
                        </li>
                        <li><x-heroicon-o-user class="size-4" /> {{ $post->authorName() }}</li>
                        <li><x-heroicon-o-clock class="size-4" /> {{ trans_choice(':count min de leitura|:count min de leitura', $post->readingMinutes()) }}</li>
                    </ul>

                    <x-rich-content :content="$post->localizedContent()" class="post-content" />

                    <div class="post-footer">
                        @if ($post->tags->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2">
                                <x-heroicon-o-tag class="size-5 text-primary-500" />
                                @foreach ($post->tags as $tag)
                                    <a href="{{ $tag->url() }}" class="blog-tag">{{ $tag->getTranslation('name', $locale) }}</a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Partilha nas redes sociais (RF04), sem scripts de terceiros --}}
                        <div class="flex flex-wrap items-center gap-2" x-data="{ copied: false }">
                            <span class="mr-1 font-heading text-sm font-semibold text-heading">{{ __('Partilhar') }}:</span>
                            @foreach ($shares as $network => [$label, $url])
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="share-button" aria-label="{{ __('Partilhar no :rede', ['rede' => $label]) }}" data-track="share-{{ $network }}">
                                    <x-social-icon :name="$network" class="size-4" />
                                </a>
                            @endforeach
                            <button type="button" class="share-button" x-on:click="navigator.clipboard.writeText('{{ $post->url() }}'); copied = true; setTimeout(() => copied = false, 2000)" aria-label="{{ __('Copiar ligação') }}">
                                <x-heroicon-o-link class="size-4" x-show="! copied" />
                                <x-heroicon-o-check class="size-4" x-show="copied" x-cloak />
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            @include('blog.sidebar')
        </div>

        @if ($previous || $next)
            <nav class="container-site mt-12 grid gap-6 sm:grid-cols-2" aria-label="{{ __('Outros artigos') }}">
                @if ($previous)
                    <a href="{{ $previous->url() }}" class="post-nav">
                        <span class="post-nav__label"><x-heroicon-o-arrow-long-left class="size-5" /> {{ __('Notícia anterior') }}</span>
                        <span class="post-nav__title">{{ $previous->getTranslation('title', $locale) }}</span>
                    </a>
                @else
                    <span></span>
                @endif
                @if ($next)
                    <a href="{{ $next->url() }}" class="post-nav text-right">
                        <span class="post-nav__label justify-end">{{ __('Notícia seguinte') }} <x-heroicon-o-arrow-long-right class="size-5" /></span>
                        <span class="post-nav__title">{{ $next->getTranslation('title', $locale) }}</span>
                    </a>
                @endif
            </nav>
        @endif
    </section>
</x-layouts.site>
