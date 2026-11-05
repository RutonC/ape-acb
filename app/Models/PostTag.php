<?php

namespace App\Models;

use Database\Factories\PostTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['slug', 'name'])]
class PostTag extends Model
{
    /** @use HasFactory<PostTagFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function url(?string $locale = null): string
    {
        return lroute('blog.tag', ['slug' => $this->slug], $locale);
    }
}
