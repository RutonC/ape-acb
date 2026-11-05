<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Blog (RF04): listagem com pesquisa, arquivo por categoria e etiqueta,
 * artigo com partilha e pré-visualização de rascunhos.
 */
class BlogController extends Controller
{
    private const PER_PAGE = 6;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $page = Page::published()->where('slug', 'noticias')->first();

        $posts = $this->listing()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                foreach (array_keys(config('site.locales')) as $locale) {
                    $query->orWhere("title->{$locale}", 'like', "%{$search}%")
                        ->orWhere("excerpt->{$locale}", 'like', "%{$search}%");
                }
            }))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'heading' => $search !== '' ? __('Pesquisa: :termo', ['termo' => $search]) : ($page?->getTranslation('title', app()->getLocale()) ?: __('Notícias')),
            'search' => $search,
            'showIntro' => $search === '' && $posts->onFirstPage(),
            'page' => $page,
            ...$this->sidebar(),
        ]);
    }

    public function category(string $slug): View
    {
        $category = PostCategory::where('slug', $slug)->firstOrFail();

        return view('blog.index', [
            'posts' => $this->listing()->where('post_category_id', $category->id)->paginate(self::PER_PAGE),
            'heading' => $category->getTranslation('name', app()->getLocale()),
            'crumb' => __('Categoria'),
            'search' => '',
            'showIntro' => false,
            'page' => null,
            ...$this->sidebar(),
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = PostTag::where('slug', $slug)->firstOrFail();

        return view('blog.index', [
            'posts' => $this->listing()->whereHas('tags', fn (Builder $query) => $query->whereKey($tag->id))->paginate(self::PER_PAGE),
            'heading' => '#'.$tag->getTranslation('name', app()->getLocale()),
            'crumb' => __('Etiqueta'),
            'search' => '',
            'showIntro' => false,
            'page' => null,
            ...$this->sidebar(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::published()->with(['category', 'tags', 'author'])->where('slug', $slug)->firstOrFail();

        return $this->renderPost($post);
    }

    public function preview(Post $post, string $locale): View
    {
        app()->setLocale($locale);

        return $this->renderPost($post->load(['category', 'tags', 'author']), preview: true);
    }

    private function renderPost(Post $post, bool $preview = false): View
    {
        $previous = $post->published_at
            ? Post::published()->where('published_at', '<', $post->published_at)->latestFirst()->first()
            : null;

        $next = $post->published_at
            ? Post::published()->where('published_at', '>', $post->published_at)->orderBy('published_at')->first()
            : null;

        return view('blog.show', [
            'post' => $post,
            'previous' => $previous,
            'next' => $next,
            'preview' => $preview,
            ...$this->sidebar(),
        ]);
    }

    /**
     * @return Builder<Post>
     */
    private function listing(): Builder
    {
        return Post::published()->with(['category', 'author'])->latestFirst();
    }

    /**
     * Dados da barra lateral: artigos recentes, categorias e etiquetas em uso.
     *
     * @return array<string, mixed>
     */
    private function sidebar(): array
    {
        return [
            'recentPosts' => Post::published()->latestFirst()->limit(4)->get(),
            'categories' => PostCategory::query()
                ->withCount(['posts' => fn (Builder $query) => $query->published()])
                ->orderBy('sort_order')
                ->get()
                ->where('posts_count', '>', 0)
                ->values(),
            'tags' => PostTag::query()
                ->whereHas('posts', fn (Builder $query) => $query->published())
                ->orderBy('slug')
                ->get(),
        ];
    }
}
