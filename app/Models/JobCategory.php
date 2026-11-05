<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Área de actividade das vagas (Mecânica, Operador…).
 */
#[Fillable(['slug', 'name', 'icon', 'sort_order'])]
class JobCategory extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * @return HasMany<JobOpening, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }

    public function url(): string
    {
        return lroute('jobs').'?categoria='.$this->slug;
    }
}
