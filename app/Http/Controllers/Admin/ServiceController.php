<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceKind;
use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ServiceResource;
use App\Models\Service;
use App\Support\Admin\ListQuery;
use App\Support\Admin\Translations;
use App\Support\IconOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Serviços do catálogo. As rotas passam o tipo e exigem a área "services".
 */
class ServiceController extends Controller
{
    use ManagesRecords;

    private const TRANSLATABLE = ['title', 'tagline', 'summary', 'intro', 'details', 'audience', 'timeline', 'meta_title', 'meta_description'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $kind = $this->kind($request);
        $items = ListQuery::for($this->query($kind), $request)
            ->search(['title->pt', 'title->en', 'slug'])
            ->filter('published', fn ($query, string $value) => $query->where('is_published', $value === '1'))
            ->sort(['sort_order', 'updated_at'], 'sort_order')
            ->paginate(50);

        return ServiceResource::collection($items);
    }

    public function show(Request $request, string $id): ServiceResource
    {
        $kind = $this->kind($request);

        return new ServiceResource($this->query($kind)->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $kind = $this->kind($request);
        $service = new Service(['kind' => $kind, 'sort_order' => $this->nextSortOrder($this->query($kind))]);
        $this->save($service, $this->validated($request, $kind));

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $id): ServiceResource
    {
        $kind = $this->kind($request);
        $service = $this->query($kind)->findOrFail($id);

        $this->save($service, $this->validated($request, $kind, $service));

        return new ServiceResource($service->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $kind = $this->kind($request);
        $this->query($kind)->findOrFail($id)->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->reorderRecords($request, $this->query($this->kind($request)));
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        return $this->bulkDeleteRecords($request, $this->query($this->kind($request)));
    }

    /** Tipo definido pela rota (services -> service). */
    private function kind(Request $request): string
    {
        return (string) $request->route('kind');
    }

    private function query(string $kind): Builder
    {
        return Service::query()->where('kind', ServiceKind::from($kind));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $kind, ?Service $service = null): array
    {
        $icons = array_keys(IconOptions::all());

        $rules = [
            'slug' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('services', 'slug')->where('kind', $kind)->ignore($service?->id),
            ],
            'icon' => ['nullable', Rule::in($icons)],
            'image_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            'external_url' => ['nullable', 'url:http,https', 'max:255'],
            'external_label' => ['nullable', 'string', 'max:80'],
            'is_published' => ['boolean'],
            ...Translations::rules('title', ['string', 'max:80'], required: true),
            ...Translations::rules('tagline', ['string', 'max:160']),
            ...Translations::rules('summary', ['string', 'max:200']),
            ...Translations::rules('intro', ['string', 'max:5000']),
            ...Translations::rules('audience', ['string', 'max:255']),
            ...Translations::rules('timeline', ['string', 'max:255']),
            ...Translations::rules('meta_title', ['string', 'max:70']),
            ...Translations::rules('meta_description', ['string', 'max:160']),
            ...Translations::rules('details', ['array', 'max:20']),
        ];

        foreach (Translations::locales() as $locale) {
            $rules["details.{$locale}.*.title"] = ['required', 'string', 'max:120'];
            $rules["details.{$locale}.*.icon"] = ['nullable', Rule::in($icons)];
            $rules["details.{$locale}.*.content"] = ['nullable', 'string', 'max:10000'];
        }

        $data = $request->validate($rules, [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífenes.',
            'slug.unique' => 'Já existe um '.'serviço'.' com este endereço.',
            'details.*.*.title.required' => 'Cada secção precisa de um título.',
        ], Translations::attributes([
            'title' => 'nome', 'tagline' => 'frase de destaque', 'summary' => 'resumo', 'intro' => 'introdução',
            'audience' => 'para quem', 'timeline' => 'prazo típico', 'meta_title' => 'título SEO', 'meta_description' => 'descrição SEO',
        ]));

        // Secções só com os campos conhecidos.
        foreach ($data['details'] ?? [] as $locale => $sections) {
            $data['details'][$locale] = array_values(array_map(
                fn (array $section) => ['title' => $section['title'], 'icon' => $section['icon'] ?? null, 'content' => $section['content'] ?? null],
                (array) $sections,
            ));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Service $service, array $data): void
    {
        $service->fill(collect($data)->only(['slug', 'icon', 'image_id', 'external_url', 'external_label', 'is_published'])->all());
        Translations::fill($service, $data, self::TRANSLATABLE);
        $service->save();
    }
}
