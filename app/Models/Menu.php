<?php

namespace App\Models;

use App\Enums\MenuLocation;
use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Menu editável no painel. Cada item:
 * ['label' => ['pt' => ..., 'en' => ...], 'link' => [...Links], 'children' => [...]].
 *
 * @property MenuLocation $location
 * @property array<int, array<string, mixed>> $items
 */
#[Fillable(['location', 'name', 'items'])]
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
            'items' => 'array',
        ];
    }
}
