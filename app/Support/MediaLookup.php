<?php

namespace App\Support;

use App\Models\Media;

/**
 * Registos da biblioteca de média lidos uma única vez por pedido. Listas de
 * imagens (galerias) chamam prime() para os carregar numa só consulta.
 */
class MediaLookup
{
    /** @var array<int, Media|null> */
    private array $items = [];

    /**
     * @param  array<int, int|string|null>  $ids
     */
    public function prime(array $ids): void
    {
        $missing = array_diff(array_unique(array_filter(array_map('intval', $ids))), array_keys($this->items));

        if ($missing === []) {
            return;
        }

        $found = Media::whereKey($missing)->get()->keyBy('id');

        foreach ($missing as $id) {
            $this->items[$id] = $found->get($id);
        }
    }

    public function find(int|string|null $id): ?Media
    {
        if (blank($id)) {
            return null;
        }

        $this->prime([$id]);

        return $this->items[(int) $id];
    }
}
