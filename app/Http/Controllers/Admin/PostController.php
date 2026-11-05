<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PostResource;
use App\Models\Post;
use App\Support\Admin\ListQuery;
use App\Support\Admin\Translations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Artigos do blog. Apagar envia para a lixeira (soft delete); daí podem ser
 * restaurados ou apagados definitivamente.
 */
class PostController extends Controller
{
    private const TRANSLATABLE = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = ListQuery::for(Post::query()->with(['category', 'tags', 'author']), $request)
            ->search(['title->pt', 'title->en', 'slug'])
            ->filter('state', fn ($query, string $state) => match ($state) {
                'draft' => $query->where('status', PageStatus::Draft),
                'scheduled' => $query->where('status', PageStatus::Published)->where('published_at', '>', now()),
                'published' => $query->where('status', PageStatus::Published)->where('published_at', '<=', now()),
                default => null,
            })
            ->filter('category', fn ($query, string $id) => $query->where('post_category_id', (int) $id))
            ->filter('tag', fn ($query, string $id) => $query->whereHas('tags', fn ($q) => $q->whereKey((int) $id)))
            ->filter('trashed', fn ($query, string $value) => $value === 'only' ? $query->onlyTrashed() : null)
            ->sort(['published_at', 'updated_at', 'created_at'], '-published_at')
            ->paginate();

        return PostResource::collection($posts);
    }

    public function show(int $id): PostResource
    {
        return new PostResource($this->find($id)->load(['category', 'tags', 'author']));
    }

    public function store(Request $request): JsonResponse
    {
        $post = new Post;
        $this->save($post, $this->validated($request));

        return (new PostResource($post->load(['category', 'tags', 'author'])))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): PostResource
    {
        $post = $this->find($id);
        $this->save($post, $this->validated($request, $post));

        return new PostResource($post->fresh(['category', 'tags', 'author']));
    }

    /** Envia para a lixeira. */
    public function destroy(int $id): JsonResponse
    {
        $this->find($id)->delete();

        return response()->json(status: 204);
    }

    public function restore(int $id): PostResource
    {
        $post = $this->find($id);
        $post->restore();

        return new PostResource($post->load(['category', 'tags', 'author']));
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $this->find($id)->forceDelete();

        return response()->json(status: 204);
    }

    /**
     * Acções em massa: "trash" (lixeira), "restore" ou "force" (apagar definitivamente).
     */
    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['trash', 'restore', 'force'])],
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $posts = Post::withTrashed()->whereKey($data['ids'])->get();

        foreach ($posts as $post) {
            match ($data['action']) {
                'trash' => $post->trashed() ?: $post->delete(),
                'restore' => $post->restore(),
                'force' => $post->forceDelete(),
            };
        }

        return response()->json(status: 204);
    }

    /**
     * Endereço assinado (2 horas) para ver o artigo como está guardado, mesmo em rascunho.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $locale = in_array($request->query('locale'), Translations::locales(), true) ? $request->query('locale') : config('site.default_locale');

        return response()->json(['url' => $this->find($id)->previewUrl($locale)]);
    }

    private function find(int $id): Post
    {
        return Post::withTrashed()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(['categoria', 'etiqueta']),
                Rule::unique('posts', 'slug')->ignore($post?->id),
            ],
            'status' => ['required', new Enum(PageStatus::class)],
            'published_at' => ['nullable', 'date'],
            'post_category_id' => ['nullable', 'integer', Rule::exists('post_categories', 'id')],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['integer', Rule::exists('post_tags', 'id')],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'cover_image_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            ...Translations::rules('title', ['string', 'max:160'], required: true),
            ...Translations::rules('excerpt', ['string', 'max:280']),
            ...Translations::rules('content', ['string', 'max:200000'], required: true),
            ...Translations::rules('meta_title', ['string', 'max:70']),
            ...Translations::rules('meta_description', ['string', 'max:160']),
        ], [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífenes.',
            'slug.unique' => 'Já existe um artigo com este endereço (veja também a lixeira).',
            'slug.not_in' => 'Este endereço está reservado pelo blog.',
        ], [
            ...Translations::attributes(['title' => 'título', 'excerpt' => 'resumo', 'content' => 'texto', 'meta_title' => 'título SEO', 'meta_description' => 'descrição SEO']),
            'published_at' => 'data de publicação', 'post_category_id' => 'categoria', 'tags' => 'etiquetas', 'author_id' => 'autor',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Post $post, array $data): void
    {
        $post->fill(collect($data)->only(['slug', 'status', 'published_at', 'post_category_id', 'author_id', 'cover_image_id'])->all());
        Translations::fill($post, $data, self::TRANSLATABLE);
        $post->save();
        $post->tags()->sync($data['tags'] ?? []);
    }
}
