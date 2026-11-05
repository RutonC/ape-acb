<?php

namespace App\Support;

use App\Models\JobOpening;
use Illuminate\Support\Collection;

/**
 * Locais que existem nas vagas publicadas, com o número de vagas de cada um.
 */
class JobLocations
{
    /**
     * @return Collection<int, array{name: string, total: int, open: int}>
     */
    public static function all(): Collection
    {
        $locale = app()->getLocale();

        return JobOpening::query()->get()
            ->filter(fn (JobOpening $job) => filled($job->getTranslation('location', $locale)))
            ->groupBy(fn (JobOpening $job) => $job->getTranslation('location', $locale))
            ->map(fn (Collection $jobs, string $name) => [
                'name' => $name,
                'total' => $jobs->count(),
                'open' => $jobs->filter(fn (JobOpening $job) => $job->isOpen())->count(),
            ])
            ->sortByDesc('total')
            ->values();
    }
}
