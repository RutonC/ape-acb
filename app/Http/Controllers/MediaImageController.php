<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Support\Glide;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve as versões redimensionadas das imagens da biblioteca de média.
 */
class MediaImageController extends Controller
{
    public function __invoke(Request $request, string $path): Response
    {
        abort_unless(Glide::validate($path, $request->query()), 403);

        $media = Media::where('path', $path)->first();

        abort_unless($media?->isResizable(), 404);

        $cached = Glide::server($media->disk)->makeImage($path, $request->query());

        return response()->file(storage_path('app/'.$cached), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
