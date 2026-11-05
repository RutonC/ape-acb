<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda ficheiros carregados no painel na biblioteca de média
 * (media/AAAA/MM/nome.ext no disco público) e regista as dimensões.
 */
class MediaUploader
{
    /** Extensões aceites (imagens e PDF). */
    public const VIDEO_EXTENSIONS = ['mp4', 'webm'];

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg', 'pdf', ...self::VIDEO_EXTENSIONS];

    public static function store(UploadedFile $file): Media
    {
        $disk = config('media.disk');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $directory = trim(config('media.directory'), '/').'/'.now()->format('Y/m');
        $name = static::uniqueName($disk, $directory, Str::slug($original) ?: 'ficheiro', $ext);

        $path = $file->storeAs($directory, "{$name}.{$ext}", ['disk' => $disk, 'visibility' => 'public']);

        [$width, $height] = static::dimensions($file, $ext);

        return Media::create([
            'disk' => $disk,
            'directory' => $directory,
            'visibility' => 'public',
            'name' => $name,
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'size' => $file->getSize(),
            'type' => $ext === 'svg' ? 'image/svg+xml' : ($file->getMimeType() ?: 'application/octet-stream'),
            'ext' => $ext,
            'title' => Str::of($original)->replace(['-', '_'], ' ')->squish()->limit(120, '')->value() ?: null,
        ]);
    }

    /**
     * SVG com scripts, eventos ou conteúdo externo pode executar código no
     * domínio do site quando aberto directamente: esses ficheiros são recusados.
     */
    public static function isUnsafeSvg(UploadedFile $file): bool
    {
        $content = (string) file_get_contents($file->getRealPath());

        return (bool) preg_match('/<script|<foreignObject|\bon[a-z]+\s*=|javascript:|<!ENTITY|xlink:href\s*=\s*["\']\s*(?!#)/i', $content);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private static function dimensions(UploadedFile $file, string $ext): array
    {
        if ($ext === 'svg') {
            return static::svgDimensions((string) file_get_contents($file->getRealPath()));
        }

        $size = @getimagesize($file->getRealPath());

        return $size ? [(int) $size[0], (int) $size[1]] : [null, null];
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private static function svgDimensions(string $svg): array
    {
        if (preg_match('/<svg[^>]*\bviewBox\s*=\s*["\']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/i', $svg, $match)) {
            return [(int) round((float) $match[1]), (int) round((float) $match[2])];
        }

        if (preg_match('/<svg[^>]*\bwidth\s*=\s*["\']([\d.]+)/i', $svg, $w) && preg_match('/<svg[^>]*\bheight\s*=\s*["\']([\d.]+)/i', $svg, $h)) {
            return [(int) round((float) $w[1]), (int) round((float) $h[1])];
        }

        return [null, null];
    }

    private static function uniqueName(string $disk, string $directory, string $base, string $ext): string
    {
        $name = Str::limit($base, 80, '');
        $candidate = $name;
        $i = 2;

        while (Storage::disk($disk)->exists("{$directory}/{$candidate}.{$ext}")) {
            $candidate = "{$name}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
