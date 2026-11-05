<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\MediaResource;
use App\Models\Media;
use App\Support\Admin\ListQuery;
use App\Support\MediaUploader;
use App\Support\MediaUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Biblioteca de média. Ver e carregar ficheiros está aberto a todo o painel
 * (os formulários de conteúdo escolhem imagens daqui); editar e apagar
 * exigem a área "media".
 */
class MediaController extends Controller
{
    /** Limite da aplicação para cada ficheiro (o PHP pode ter um limite menor). */
    private const MAX_KB = 10240;

    private const MAX_VIDEO_KB = 102400;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Media::query();

        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
            $query->whereKey($ids);
        }

        $media = ListQuery::for($query, $request)
            ->search(['name', 'title', 'alt'])
            ->filter('type', fn ($query, string $type) => match ($type) {
                'image' => $query->whereIn('ext', [...config('media.resizable'), 'svg']),
                'video' => $query->whereIn('ext', MediaUploader::VIDEO_EXTENSIONS),
                'document' => $query->whereNotIn('ext', [...config('media.resizable'), 'svg', ...MediaUploader::VIDEO_EXTENSIONS]),
                default => null,
            })
            ->sort(['created_at', 'name', 'size'], '-created_at')
            ->paginate(24);

        return MediaResource::collection($media)->additional(['upload' => static::uploadLimits()]);
    }

    public function show(Media $media): JsonResponse
    {
        return response()->json([
            'data' => (new MediaResource($media))->resolve(),
            'usage' => MediaUsage::for($media->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'file',
                'max:'.self::MAX_VIDEO_KB,
                function (string $attribute, UploadedFile $file, $fail) {
                    $isVideo = in_array(strtolower($file->getClientOriginalExtension()), MediaUploader::VIDEO_EXTENSIONS, true);

                    if (! $isVideo && $file->getSize() > self::MAX_KB * 1024) {
                        $fail('Cada imagem ou documento pode ter no máximo 10 MB.');
                    }
                },
                'extensions:'.implode(',', MediaUploader::EXTENSIONS),
                function (string $attribute, UploadedFile $file, $fail) {
                    if (strtolower($file->getClientOriginalExtension()) === 'svg' && MediaUploader::isUnsafeSvg($file)) {
                        $fail('O SVG "'.$file->getClientOriginalName().'" contém scripts ou referências externas e foi recusado.');
                    }
                },
            ],
        ], [
            'files.*.uploaded' => 'O ficheiro excede o tamanho máximo permitido pelo servidor.',
            'files.*.max' => 'Cada vídeo pode ter no máximo 100 MB.',
            'files.*.extensions' => 'Formato não aceite. Use JPG, PNG, WebP, GIF, AVIF, SVG, PDF, MP4 ou WebM.',
        ]);

        $created = collect($request->file('files'))->map(fn (UploadedFile $file) => MediaUploader::store($file));

        return MediaResource::collection($created)->response()->setStatusCode(201);
    }

    public function update(Request $request, Media $media): MediaResource
    {
        Gate::authorize('media');

        $media->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['title' => 'título', 'alt' => 'texto alternativo', 'caption' => 'legenda', 'description' => 'descrição']));

        return new MediaResource($media);
    }

    public function destroy(Media $media): JsonResponse
    {
        Gate::authorize('media');

        $media->delete();

        return response()->json(status: 204);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        Gate::authorize('media');

        $data = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer', Rule::exists('curator', 'id')]]);

        Media::whereKey($data['ids'])->get()->each->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array{max_size: int, extensions: array<int, string>}
     */
    private static function uploadLimits(): array
    {
        $php = min(static::iniBytes(ini_get('upload_max_filesize')), static::iniBytes(ini_get('post_max_size')));

        return [
            'max_size' => min(self::MAX_VIDEO_KB * 1024, $php > 0 ? $php : PHP_INT_MAX),
            'extensions' => MediaUploader::EXTENSIONS,
        ];
    }

    private static function iniBytes(string|false $value): int
    {
        $value = trim((string) $value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
