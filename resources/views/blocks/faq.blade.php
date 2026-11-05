<div class="container-site max-w-[1000px]">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <div class="space-y-4" x-data="{ open: 0 }">
        @foreach ($data['items'] ?? [] as $item)
            <div class="faq-item" :class="{ 'is-open': open === {{ $loop->index }} }" data-aos="fade-up">
                <h3>
                    <button type="button" class="faq-item__question" x-on:click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="open === {{ $loop->index }}">
                        <span>{{ $item['question'] ?? '' }}</span>
                        <x-heroicon-o-plus class="size-5 shrink-0 transition-transform duration-300" x-bind:class="{ 'rotate-45': open === {{ $loop->index }} }" />
                    </button>
                </h3>
                <div x-show="open === {{ $loop->index }}" x-collapse @if (! $loop->first) x-cloak @endif>
                    <p class="faq-item__answer">{!! nl2br(e($item['answer'] ?? '')) !!}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
