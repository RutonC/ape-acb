<div class="container-site">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <div class="swiper" x-data="slider({ slidesPerView: 1, spaceBetween: 30, breakpoints: { 768: { slidesPerView: 2 }, 1200: { slidesPerView: 3 } } })" data-aos="fade-up">
        <div class="swiper-wrapper">
            @foreach ($data['items'] ?? [] as $item)
                <figure class="swiper-slide testimonial">
                    <x-heroicon-s-chat-bubble-bottom-center-text class="testimonial__quote-icon" />
                    <blockquote class="testimonial__quote">{{ $item['quote'] ?? '' }}</blockquote>

                    <div class="testimonial__rating" aria-label="{{ (int) ($item['rating'] ?? 5) }}/5">
                        @for ($i = 0; $i < (int) ($item['rating'] ?? 5); $i++)
                            <x-heroicon-s-star class="size-4" />
                        @endfor
                    </div>

                    <figcaption class="testimonial__author">
                        @if (filled($item['photo'] ?? null))
                            <x-media :id="$item['photo']" :widths="[160]" sizes="64px" class="testimonial__photo" />
                        @else
                            <span class="testimonial__photo testimonial__photo--initial">{{ mb_substr($item['name'] ?? '?', 0, 1) }}</span>
                        @endif
                        <span>
                            <span class="testimonial__name">{{ $item['name'] ?? '' }}</span>
                            @if (filled($item['role'] ?? null))
                                <span class="testimonial__role">{{ $item['role'] }}</span>
                            @endif
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
        <div class="swiper-pagination !static mt-10"></div>
    </div>
</div>
