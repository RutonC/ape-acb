<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use App\Models\JobOpening;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Categorias e vagas migradas de ape.acb.co.mz (database/data/ape.json). As vagas
 * são de 2018, por isso entram encerradas e reabrem-se no painel. Só cria o que
 * ainda não existe: as edições feitas no painel prevalecem.
 */
class JobSeeder extends Seeder
{
    private const CATEGORIES = [
        'administracao' => ['Administração', 'clipboard-document-check'],
        'informatica' => ['Informática', 'code-bracket-square'],
        'gestao' => ['Gestão', 'presentation-chart-line'],
        'contabilidade-e-auditoria' => ['Contabilidade e Auditoria', 'banknotes'],
        'operador' => ['Operador', 'user-group'],
        'mecanica' => ['Mecânica', 'wrench-screwdriver'],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::CATEGORIES as $slug => [$name, $icon]) {
            JobCategory::firstOrCreate(['slug' => $slug], [
                'name' => ['pt' => $name],
                'icon' => $icon,
                'sort_order' => ++$position,
            ]);
        }

        $data = json_decode((string) file_get_contents(database_path('data/ape.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['jobs'] as $index => $job) {
            if (JobOpening::where('slug', $job['slug'])->exists()) {
                continue;
            }

            JobOpening::create([
                'slug' => $job['slug'],
                'job_category_id' => JobCategory::where('slug', $job['category'])->value('id'),
                'title' => ['pt' => $job['title']],
                'summary' => ['pt' => $job['summary']],
                'description' => ['pt' => $job['description']],
                'contract_type' => $job['contract'] ? ['pt' => $job['contract']] : null,
                'location' => $job['location'] ? ['pt' => $job['location']] : null,
                'positions' => $job['positions'],
                'cover_image_id' => SeedMedia::fromPublic($job['cover'], $job['title'], $job['title'])->id,
                'is_open' => false,
                'posted_at' => Carbon::parse($job['date'])->setTimezone(config('app.timezone')),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
