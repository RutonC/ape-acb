<?php

namespace App\Models;

use App\Enums\PageStatus;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\Translatable\HasTranslations;

/**
 * Artigo do blog (RF04): categoria, etiquetas, autor, data e partilha.
 * Só é público quando publicado e com data de publicação já alcançada
 * (permite agendar artigos).
 *
 * @property PageStatus $status
 * @property Carbon|null $published_at
 */
#[Fillable([
    'slug', 'title', 'excerpt', 'content', 'cover_image_id', 'post_category_id', 'author_id',
    'status', 'published_at', 'meta_title', 'meta_description',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if ($post->status === PageStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published)->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function isVisible(): bool
    {
        return $this->status === PageStatus::Published && $this->published_at?->isPast();
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /**
     * @return BelongsToMany<PostTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PostTag::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    /**
     * Conteúdo no idioma actual, com recurso ao predefinido.
     */
    public function localizedContent(): string
    {
        return $this->getTranslation('content', app()->getLocale(), useFallbackLocale: false)
            ?: $this->getTranslation('content', config('site.default_locale'), useFallbackLocale: false)
            ?: '';
    }

    /**
     * Minutos estimados de leitura (≈ 200 palavras por minuto).
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->localizedContent())) / 200));
    }

    public function authorName(): string
    {
        return $this->author?->name ?: __('Equipa APE');
    }

    public function url(?string $locale = null): string
    {
        return lroute('blog.show', ['slug' => $this->slug], $locale);
    }

    public function previewUrl(?string $locale = null): string
    {
        return URL::temporarySignedRoute('posts.preview', now()->addHours(2), [
            'post' => $this->getKey(),
            'locale' => $locale ?? config('site.default_locale'),
        ]);
    }
}
