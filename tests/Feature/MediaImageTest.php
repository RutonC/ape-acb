<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaImageTest extends TestCase
{
    use RefreshDatabase;

    private function image(): Media
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs('media', UploadedFile::fake()->image('foto.jpg', 800, 600), 'foto.jpg');

        return Media::create([
            'disk' => 'public', 'directory' => 'media', 'visibility' => 'public', 'name' => 'foto',
            'path' => 'media/foto.jpg', 'width' => 800, 'height' => 600, 'size' => 1000,
            'type' => 'image/jpeg', 'ext' => 'jpg', 'alt' => 'Uma foto',
        ]);
    }

    public function test_signed_urls_serve_resized_images(): void
    {
        $media = $this->image();

        $response = $this->get($media->glideUrl(['w' => 200, 'fm' => 'webp']));

        $response->assertOk();
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function test_unsigned_or_tampered_urls_are_refused(): void
    {
        $media = $this->image();

        $this->get('/img/media/foto.jpg?w=200')->assertForbidden();
        $this->get(str_replace('w=200', 'w=1900', $media->glideUrl(['w' => 200])))->assertForbidden();
    }

    public function test_media_component_renders_webp_srcset(): void
    {
        $media = $this->image();

        $html = $this->blade('<x-media :id="$id" />', ['id' => $media->id]);

        $html->assertSee('srcset', false)->assertSee('fm=webp', false)->assertSee('alt="Uma foto"', false);
    }

    public function test_deleting_media_removes_the_file(): void
    {
        $media = $this->image();

        $media->delete();

        Storage::disk('public')->assertMissing('media/foto.jpg');
    }
}
