<?php

namespace App\Http\Resources\Admin;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isImage = $this->isResizable() || $this->isSvg();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_name' => $this->name.'.'.$this->ext,
            'title' => $this->title,
            'alt' => $this->alt,
            'caption' => $this->caption,
            'description' => $this->description,
            'ext' => $this->ext,
            'type' => $this->type,
            'is_image' => $isImage,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->size,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'preview_url' => $this->isResizable() ? $this->glideUrl(['w' => 800, 'fm' => 'webp', 'q' => 80]) : ($this->isSvg() ? $this->url : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
