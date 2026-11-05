<?php

namespace App\Models;

use Database\Factories\JobOpeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Vaga de emprego. Aberta enquanto is_open e a data limite não passou. Os campos
 * opcionais vazios (salário, experiência, data limite) não aparecem no site.
 *
 * @property Carbon|null $closes_at
 * @property Carbon|null $posted_at
 */
#[Fillable(['job_category_id', 'slug', 'title', 'contract_type', 'location', 'summary', 'description', 'requirements', 'positions', 'experience', 'salary', 'cover_image_id', 'is_open', 'posted_at', 'closes_at', 'sort_order'])]
class JobOpening extends Model
{
    /** @use HasFactory<JobOpeningFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['title', 'contract_type', 'location', 'summary', 'description', 'requirements'];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'posted_at' => 'date',
            'closes_at' => 'date',
            'positions' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<JobOpening>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('is_open', true)
            ->where(fn (Builder $query) => $query->whereNull('closes_at')->orWhereDate('closes_at', '>=', today()));
    }

    /**
     * @param  Builder<JobOpening>  $query
     */
    public function scopeClosed(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->where('is_open', false)->orWhereDate('closes_at', '<', today()));
    }

    /**
     * Vagas abertas primeiro; dentro de cada grupo, as mais recentes.
     *
     * @param  Builder<JobOpening>  $query
     */
    public function scopeOpenFirst(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN is_open = 1 AND (closes_at IS NULL OR closes_at >= ?) THEN 0 ELSE 1 END', [today()->toDateString()])
            ->orderByDesc('posted_at')
            ->orderByDesc('id');
    }

    public function isOpen(): bool
    {
        return $this->is_open && ($this->closes_at === null || ! $this->closes_at->lt(today()));
    }

    /**
     * @return BelongsTo<JobCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function url(?string $locale = null): string
    {
        return lroute('jobs.show', ['slug' => $this->slug], $locale);
    }
}
