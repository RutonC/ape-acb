<div @class(['container-site', 'max-w-[860px]' => ($data['width'] ?? 'narrow') === 'narrow'])>
    @if (filled($data['title'] ?? null))
        <h2 class="section-heading__title mb-6" data-aos="fade-up">{{ $data['title'] }}</h2>
    @endif

    <x-rich-content :content="$data['content'] ?? null" data-aos="fade-up" />
</div>
