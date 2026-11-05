<div class="container-site">
    <x-section-heading :subtitle="$data['subtitle'] ?? null" :title="$data['title'] ?? null" :description="$data['description'] ?? null" :align="$data['heading_align'] ?? 'center'" />

    <x-gallery :items="$data['items'] ?? []" :columns="$data['columns'] ?? 3" :filters="$data['show_filters'] ?? true" />
</div>
