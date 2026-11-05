@php
    $members = \App\Models\TeamMember::published()->get();
    $locale = app()->getLocale();
    $grid = ((int) ($data['columns'] ?? 4)) === 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4';
@endphp

{{-- Equipa (rs-team do tema); sem membros publicados, nada é mostrado --}}
@if ($members->isNotEmpty())
    <div class="container-site">
        <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

        <div class="grid gap-[30px] {{ $grid }}">
            @foreach ($members as $member)
                <article class="team-card" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4) * 100 }}">
                    <div class="team-card__media">
                        @if ($member->photo_id)
                            <x-media :id="$member->photo_id" :widths="[600, 400]" sizes="(min-width: 1024px) 25vw, 50vw" class="team-card__photo" />
                        @else
                            <span class="team-card__initials" aria-hidden="true">{{ collect(explode(' ', $member->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
                        @endif

                        @if ($member->linkedin_url || $member->email)
                            <div class="team-card__social">
                                @if ($member->linkedin_url)
                                    <a href="{{ $member->linkedin_url }}" target="_blank" rel="noopener" aria-label="LinkedIn de {{ $member->name }}"><x-social-icon name="linkedin" class="size-4" /></a>
                                @endif
                                @if ($member->email)
                                    <a href="mailto:{{ $member->email }}" aria-label="E-mail de {{ $member->name }}"><x-heroicon-o-envelope class="size-4" /></a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="team-card__body">
                        <h3 class="team-card__name">{{ $member->name }}</h3>
                        <p class="team-card__role">{{ $member->getTranslation('role', $locale) }}</p>
                        @if ($member->getTranslation('bio', $locale))
                            <p class="mt-2 text-[15px]">{{ $member->getTranslation('bio', $locale) }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endif
