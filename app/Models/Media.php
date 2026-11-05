<?php

namespace App\Models;

use App\Support\Glide;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Ficheiro da biblioteca de média (imagens, SVG, documentos).
 *
 * @property int $id
 * @property string $disk
 * @property string|null $directory
 * @property string $visibility
 * @property string $name
 * @property string $path
 * @property int|null $width
 * @property int|null $height
 * @property int|null $size
 * @property string $type
 * @property string $ext
 * @property string|null $alt
 * @property string|null $title
 * @property string|null $description
 * @property string|null $caption
 * @property-read string $url
 * @property-read string|null $thumbnail_url
 */
class Media extends Model
{
    protected $table = 'curator';

    protected $fillable = [
        'disk', 'directory', 'visibility', 'name', 'path', 'width', 'height',
        'size', 'type', 'ext', 'alt', 'title', 'description', 'caption', 'exif', 'curations',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
            'exif' => 'array',
            'curations' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Media $media): void {
            Storage::disk($media->disk)->delete($media->path);

            if ($media->isResizable()) {
                Glide::forget($media->path, $media->disk);
            }
        });
    }

    public function isResizable(): bool
    {
        return in_array(strtolower((string) $this->ext), config('media.resizable'), true);
    }

    public function isSvg(): bool
    {
        return strtolower((string) $this->ext) === 'svg';
    }

    /**
     * @param  array<string, int|string|null>  $params
     */
    public function glideUrl(array $params = []): string
    {
        return Glide::url($this->path, $params);
    }

    /** URL directo do ficheiro original. */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk($this->disk)->url($this->path))->shouldCache();
    }

    /** Miniatura quadrada para listas do painel. */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->isResizable()
            ? $this->glideUrl(['w' => 200, 'h' => 200, 'fit' => 'crop', 'fm' => 'webp'])
            : ($this->isSvg() ? $this->url : null));
    }

    protected function prettyName(): Attribute
    {
        return Attribute::get(fn (): string => filled($this->title) ? $this->title : $this->name.'.'.$this->ext);
    }
}
