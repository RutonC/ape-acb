<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Urls\UrlBuilderFactory;
use RuntimeException;

/**
 * Imagens redimensionadas a pedido (WebP, JPG para partilhas) a partir dos
 * ficheiros da biblioteca de média. Os URLs são assinados para que ninguém
 * possa pedir tamanhos arbitrários.
 */
class Glide
{
    public static function basePath(): string
    {
        return trim((string) config('media.glide_path'), '/');
    }

    /**
     * @param  array<string, int|string|null>  $params
     */
    public static function url(string $path, array $params = []): string
    {
        return UrlBuilderFactory::create('/'.static::basePath().'/', static::token())
            ->getUrl($path, array_filter($params, fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function validate(string $path, array $params): bool
    {
        try {
            SignatureFactory::create(static::token())->validateRequest('/'.static::basePath().'/'.$path, $params);

            return true;
        } catch (SignatureException) {
            return false;
        }
    }

    public static function server(?string $disk = null): Server
    {
        return ServerFactory::create([
            'source' => Storage::disk($disk ?? config('media.disk'))->getDriver(),
            'cache' => storage_path('app'),
            'cache_path_prefix' => '.cache',
            'max_image_size' => 2000 * 2000,
            'base_url' => static::basePath(),
        ]);
    }

    public static function forget(string $path, ?string $disk = null): void
    {
        static::server($disk)->deleteCache($path);
    }

    private static function token(): string
    {
        $token = config('media.glide_token');

        if (blank($token)) {
            throw new RuntimeException('Falta MEDIA_GLIDE_TOKEN no .env (string aleatória usada para assinar os URLs das imagens).');
        }

        return $token;
    }
}
