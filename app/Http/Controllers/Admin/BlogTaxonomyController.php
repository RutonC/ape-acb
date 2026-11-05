<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Support\Admin\Translations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Categorias e etiquetas do blog (nome PT/EN, endereço e número de artigos).
 * O tipo vem da rota: "categories" ou "tags". As categorias têm ordem.
 */
class BlogTaxonomyController extends Controller
{
    use ManagesRecords;

    public function index(Request $request): JsonResponse
    {
        $model = $this->model($request);

        $items = $model::query()
            ->withCount('posts')
            ->when($request->filled('search'), fn ($query) => $query->where('name->pt', 'like', '%'.$request->string('search')->trim().'%'))
            ->when($model === PostCategory::class, fn ($query) => $query->orderBy('sort_order'), fn ($query) => $query->orderBy('slug'))
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items->map(fn (Model $item) => $this->present($item))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->model($request);
        $data = $this->validated($request);

        $item = new $model(['slug' => $data['slug']]);
        if ($model === PostCategory::class) {
            $item->sort_order = $this->nextSortOrder(PostCategory::query());
        }
        Translations::fill($item, $data, ['name']);
        $item->save();

        return response()->json(['data' => $this->present($item->loadCount('posts'))], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $item = $this->model($request)::findOrFail($request->route('id'));
        $data = $this->validated($request, $item);

        $item->slug = $data['slug'];
        Translations::fill($item, $data, ['name']);
        $item->save();

        return response()->json(['data' => $this->present($item->loadCount('posts'))]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->model($request)::findOrFail($request->route('id'))->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->reorderRecords($request, PostCategory::query());
    }

    /**
     * @return class-string<PostCategory|PostTag>
     */
    private function model(Request $request): string
    {
        return $request->route('taxonomy') === 'tags' ? PostTag::class : PostCategory::class;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Model $item = null): array
    {
        $table = $this->model($request) === PostTag::class ? 'post_tags' : 'post_categories';

        // Sem endereço, gera-se a partir do nome (útil ao criar etiquetas no formulário do artigo).
        if (blank($request->input('slug')) && filled($request->input('name.pt'))) {
            $request->merge(['slug' => Str::slug((string) $request->input('name.pt'))]);
        }

        return $request->validate([
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, 'slug')->ignore($item?->getKey())],
            ...Translations::rules('name', ['string', 'max:80'], required: true),
        ], [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífenes.',
            'slug.unique' => 'Já existe com este endereço.',
        ], [...Translations::attributes(['name' => 'nome']), 'slug' => 'endereço']);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Model $item): array
    {
        return [
            'id' => $item->getKey(),
            'slug' => $item->slug,
            'label' => $item->getTranslation('name', config('site.default_locale')),
            ...Translations::present($item),
            'sort_order' => $item->sort_order ?? null,
            'posts_count' => (int) ($item->posts_count ?? 0),
            'url' => $item->url(),
        ];
    }
}
