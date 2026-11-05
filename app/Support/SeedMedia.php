<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Regista na biblioteca de média ficheiros que acompanham o projecto
 * (ilustrações em public/images), para os seeders de conteúdo.
 */
class SeedMedia
{
    /**
     * Idempotente: se o ficheiro já foi registado, devolve o registo existente.
     * $name distingue ficheiros com o mesmo nome em pastas diferentes.
     */
    public static function fromPublic(string $publicPath, string $title, string $alt, ?string $name = null): Media
    {
        return static::fromPath(public_path($publicPath), $title, $alt, $name);
    }

    /**
     * O mesmo a partir de qualquer caminho (ex.: vídeos em database/data, fora da pasta pública).
     */
    public static function fromPath(string $source, string $title, string $alt, ?string $name = null): Media
    {
        $name ??= pathinfo($source, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $path = "media/seed/{$name}.{$ext}";

        $existing = Media::where('path', $path)->first();

        if ($existing) {
            return $existing;
        }

        $stream = fopen($source, 'rb');
        Storage::disk('public')->put($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        [$width, $height] = static::dimensions($source, $ext);

        return Media::create([
            'disk' => 'public',
            'directory' => 'media/seed',
            'visibility' => 'public',
            'name' => $name,
            'path' => $path,
            'ext' => $ext,
            'type' => File::mimeType($source) ?: ($ext === 'svg' ? 'image/svg+xml' : 'application/octet-stream'),
            'size' => File::size($source),
            'width' => $width,
            'height' => $height,
            'title' => $title,
            'alt' => $alt,
            'pretty_name' => Str::headline($name),
        ]);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private static function dimensions(string $source, string $ext): array
    {
        if ($ext === 'svg') {
            $svg = File::get($source);

            if (preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.]+)\s+([\d.]+)"/', $svg, $matches)) {
                return [(int) $matches[1], (int) $matches[2]];
            }

            return [null, null];
        }

        $size = @getimagesize($source);

        return $size ? [$size[0], $size[1]] : [null, null];
    }
}
