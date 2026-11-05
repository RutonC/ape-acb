<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Support\IconOptions;
use Illuminate\Http\JsonResponse;

/**
 * Listas de opções partilhadas pelos formulários do painel.
 */
class OptionsController extends Controller
{
    /**
     * Ícones dos cartões e secções (Heroicons de contorno), com o SVG para pré-visualizar.
     */
    public function icons(): JsonResponse
    {
        return response()->json([
            'data' => collect(IconOptions::all())->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
                'svg' => svg('heroicon-o-'.$value)->toHtml(),
            ])->values(),
        ]);
    }

    /**
     * Autores possíveis dos artigos (utilizadores activos do painel).
     */
    public function authors(): JsonResponse
    {
        return response()->json([
            'data' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])->values(),
        ]);
    }

    /**
     * Serviços (ex.: "serviço relacionado" de um projecto).
     */
    public function services(): JsonResponse
    {
        return response()->json([
            'data' => Service::query()->orderBy('kind')->orderBy('sort_order')->get()->map(fn (Service $service) => [
                'value' => $service->id,
                'label' => $service->kind->getLabel().': '.$service->getTranslation('title', config('site.default_locale')),
            ])->values(),
        ]);
    }
}
