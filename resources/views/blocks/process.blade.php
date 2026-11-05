@php
    $count = count($data['steps'] ?? []);
    $grid = match (true) {
        $count === 6 => 'md:grid-cols-2 lg:grid-cols-3',
        $count >= 5 => 'md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
        $count === 4 => 'md:grid-cols-2 lg:grid-cols-4',
        $count === 3 => 'md:grid-cols-3',
        default => 'md:grid-cols-2',
    };
@endphp

<div class="container-site">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <ol class="grid gap-[30px] {{ $grid }}">
        @foreach ($data['steps'] ?? [] as $step)
            <li class="process-step" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                <span class="process-step__number">{{ $loop->iteration }}.</span>
                <h3 class="process-step__title">{{ $step['title'] ?? '' }}</h3>
                @if (filled($step['text'] ?? null))
                    <p>{{ $step['text'] }}</p>
                @endif
            </li>
        @endforeach
    </ol>
</div>
