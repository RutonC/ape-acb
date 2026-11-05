<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Acções comuns das listagens do painel: reordenar (sort_order) e apagar em massa.
 */
trait ManagesRecords
{
    /**
     * Grava a ordem recebida (lista de ids, do primeiro ao último).
     */
    protected function reorderRecords(Request $request, Builder $query): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer']])['ids'];

        DB::transaction(function () use ($ids, $query) {
            $allowed = (clone $query)->whereKey($ids)->pluck('id')->all();

            foreach (array_values(array_intersect($ids, $allowed)) as $position => $id) {
                // Pelo modelo, para disparar os eventos (ex.: limpar caches do catálogo).
                (clone $query)->whereKey($id)->first()?->update(['sort_order' => $position + 1]);
            }
        });

        return response()->json(status: 204);
    }

    protected function bulkDeleteRecords(Request $request, Builder $query): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']])['ids'];

        $query->whereKey($ids)->get()->each->delete();

        return response()->json(status: 204);
    }

    /**
     * Próxima posição numa lista ordenada.
     */
    protected function nextSortOrder(Builder $query): int
    {
        return (int) $query->max('sort_order') + 1;
    }
}
