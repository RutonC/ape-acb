<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PageResource;
use App\Models\Page;
use App\Support\Admin\ListQuery;
use App\Support\Admin\Translations;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

/**
 * Páginas do construtor (blocos por idioma). Apagar envia para a lixeira.
 */
class PageController extends Controller
{
    private const TRANSLATABLE = ['title', 'content', 'meta_title', 'meta_description'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $pages = ListQuery::for(Page::query(), $request)
            ->search(['title->pt', 'title->en', 'slug'])
            ->filter('state', fn ($query, string $state) => match ($state) {
                'draft' => $query->where('status', PageStatus::Draft),
                'published' => $query->where('status', PageStatus::Published),
                default => null,
            })
            ->filter('trashed', fn ($query, string $value) => $value === 'only' ? $query->onlyTrashed() : null)
            ->sort(['slug', 'updated_at'], 'slug')
            ->paginate(50);

        return PageResource::collection($pages);
    }

    /**
     * Definições dos blocos para o construtor do painel.
     */
    public function blocks(): JsonResponse
    {
        return response()->json(['data' => BlockSchema::forPanel(), 'backgrounds' => BlockSchema::BACKGROUNDS]);
    }

    public function show(int $id): PageResource
    {
        return new PageResource($this->find($id));
    }

    public function store(Request $request): JsonResponse
    {
        $page = new Page;
        $this->save($page, $this->validated($request));

        return (new PageResource($page))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): PageResource
    {
        $page = $this->find($id);
        $this->save($page, $this->validated($request, $page));

        return new PageResource($page->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $page = $this->find($id);

        if ($page->is_homepage) {
            return response()->json(['message' => 'A página inicial não pode ir para a lixeira. Escolha outra página inicial primeiro.'], 422);
        }

        $page->delete();

        return response()->json(status: 204);
    }

    public function restore(int $id): PageResource
    {
        $page = $this->find($id);
        $page->restore();

        return new PageResource($page);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $this->find($id)->forceDelete();

        return response()->json(status: 204);
    }

    public function preview(Request $request, int $id): JsonResponse
    {
        $locale = in_array($request->query('locale'), Translations::locales(), true) ? $request->query('locale') : config('site.default_locale');

        return response()->json(['url' => $this->find($id)->previewUrl($locale)]);
    }

    private function find(int $id): Page
    {
        return Page::withTrashed()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(Page::RESERVED_SLUGS),
                Rule::unique('pages', 'slug')->ignore($page?->id),
            ],
            'status' => ['required', new Enum(PageStatus::class)],
            'is_homepage' => ['boolean'],
            'og_image_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            ...Translations::rules('title', ['string', 'max:160'], required: true),
            ...Translations::rules('meta_title', ['string', 'max:70']),
            ...Translations::rules('meta_description', ['string', 'max:160']),
            'content' => ['nullable', 'array'],
            'content.*' => ['nullable', 'array', 'max:40'],
        ], [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífenes (ex.: sobre-nos).',
            'slug.not_in' => 'Este endereço é usado pelo sistema.',
            'slug.unique' => 'Já existe uma página com este endereço (veja também a lixeira).',
            'content.*.max' => 'Uma página pode ter no máximo 40 blocos.',
        ], Translations::attributes(['title' => 'título', 'meta_title' => 'título SEO', 'meta_description' => 'descrição SEO']));

        // Blocos: validados e limpos segundo as definições (BlockSchema).
        $errors = [];
        foreach (Translations::locales() as $locale) {
            [$blocks, $blockErrors] = BlockData::clean((array) ($data['content'][$locale] ?? []), "content.{$locale}");
            $data['content'][$locale] = $blocks;
            $errors = [...$errors, ...$blockErrors];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Page $page, array $data): void
    {
        $page->fill(collect($data)->only(['slug', 'status', 'og_image_id'])->all());
        $page->is_homepage = (bool) ($data['is_homepage'] ?? false);
        Translations::fill($page, $data, self::TRANSLATABLE);
        $page->save();
    }
}
