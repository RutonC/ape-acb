<div class="container-site">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <div class="grid items-stretch gap-[30px] md:grid-cols-2 lg:grid-cols-3">
        @foreach ($data['plans'] ?? [] as $plan)
            <div @class(['price-table', 'price-table--featured' => $plan['highlighted'] ?? false]) data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                <h3 class="price-table__name">{{ $plan['name'] ?? '' }}</h3>
                <div class="price-table__price">
                    <span class="price-table__currency">{{ $plan['currency'] ?? '' }}</span>
                    <span class="price-table__value">{{ $plan['price'] ?? '' }}</span>
                </div>
                @if (filled($plan['period'] ?? null))
                    <p class="price-table__period">{{ $plan['period'] }}</p>
                @endif

                <ul class="price-table__features">
                    @foreach ($plan['features'] ?? [] as $feature)
                        <li>
                            <x-heroicon-s-check class="size-5 shrink-0 text-primary-500" />
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>

                <x-link-button :link="$plan['button'] ?? null" :label="$plan['button']['label'] ?? null" class="btn btn-primary mt-auto w-full" />
            </div>
        @endforeach
    </div>
</div>
