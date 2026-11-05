@php
    use App\Enums\ServiceKind;

    /** @var ServiceKind $kind */
    $title = $page?->getTranslation('title', app()->getLocale()) ?: __('Serviços');

    $grid = [
        'type' => 'cards',
        'data' => [
            'subtitle' => null,
            'title' => __('Nossos serviços'),
            'heading_align' => 'center',
            'style' => 'service',
            'columns' => 3,
            'background' => 'light',
            'items' => $items->map(fn ($item) => [
                'icon' => $item->icon,
                'title' => $item->getTranslation('title', app()->getLocale()),
                'text' => $item->getTranslation('summary', app()->getLocale()),
                'link' => ['type' => 'url', 'url' => $item->url()],
            ])->all(),
        ],
    ];
@endphp

<x-layouts.site
    :title="$title"
    :full-title="$page?->getTranslation('meta_title', app()->getLocale()) ?: null"
    :image="$page?->og_image_id"
    :description="$page?->getTranslation('meta_description', app()->getLocale()) ?: null"
>
    <x-page-banner :title="$title" :breadcrumbs="[['label' => $title]]" />

    <x-blocks :blocks="[$grid]" />

    <x-blocks :blocks="$blocks" />
</x-layouts.site>
