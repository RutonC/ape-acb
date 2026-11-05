<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use App\Support\Admin\ListQuery;
use App\Support\Admin\Translations;
use App\Support\TranslationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Vagas.
 */
class JobOpeningController extends Controller
{
    use ManagesRecords;

    private const TRANSLATABLE = ['title', 'contract_type', 'location', 'summary', 'description', 'requirements'];

    public function index(Request $request): JsonResponse
    {
        $jobs = ListQuery::for(JobOpening::query()->with('category')->withCount('applications'), $request)
            ->search(['title->pt', 'title->en', 'slug'])
            ->filter('open', fn ($query, string $value) => $query->where('is_open', $value === '1'))
            ->filter('category', fn ($query, string $value) => $query->where('job_category_id', (int) $value))
            ->sort(['sort_order', 'posted_at', 'closes_at'], '-posted_at')
            ->paginate(50);

        return response()->json([
            'data' => collect($jobs->items())->map(fn (JobOpening $job) => $this->present($job))->values(),
            'meta' => ['current_page' => $jobs->currentPage(), 'per_page' => $jobs->perPage(), 'total' => $jobs->total(), 'last_page' => $jobs->lastPage()],
        ]);
    }

    public function show(JobOpening $job): JsonResponse
    {
        return response()->json(['data' => $this->present($job->loadCount('applications'), detailed: true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $job = new JobOpening(['sort_order' => $this->nextSortOrder(JobOpening::query())]);
        $this->save($job, $this->validated($request));

        return response()->json(['data' => $this->present($job->loadCount('applications'), detailed: true)], 201);
    }

    public function update(Request $request, JobOpening $job): JsonResponse
    {
        $this->save($job, $this->validated($request, $job));

        return response()->json(['data' => $this->present($job->fresh()->loadCount('applications'), detailed: true)]);
    }

    public function destroy(JobOpening $job): JsonResponse
    {
        $job->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->reorderRecords($request, JobOpening::query());
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        return $this->bulkDeleteRecords($request, JobOpening::query());
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?JobOpening $job = null): array
    {
        return $request->validate([
            'slug' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('job_openings', 'slug')->ignore($job?->id),
            ],
            'job_category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'positions' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'experience' => ['nullable', 'string', 'max:80'],
            'salary' => ['nullable', 'string', 'max:80'],
            'cover_image_id' => ['nullable', 'integer', 'exists:curator,id'],
            'is_open' => ['boolean'],
            'posted_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            ...Translations::rules('title', ['string', 'max:120'], required: true),
            ...Translations::rules('contract_type', ['string', 'max:80']),
            ...Translations::rules('location', ['string', 'max:120']),
            ...Translations::rules('summary', ['string', 'max:400']),
            ...Translations::rules('description', ['string', 'max:20000']),
            ...Translations::rules('requirements', ['string', 'max:20000']),
        ], [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífenes.',
            'slug.unique' => 'Já existe uma vaga com este endereço.',
        ], [
            ...Translations::attributes([
                'title' => 'título', 'contract_type' => 'tipo de contrato', 'location' => 'local',
                'summary' => 'resumo', 'description' => 'descrição', 'requirements' => 'requisitos',
            ]),
            'job_category_id' => 'categoria',
            'positions' => 'número de vagas',
            'experience' => 'experiência',
            'salary' => 'salário',
            'cover_image_id' => 'imagem',
            'posted_at' => 'data de publicação',
            'closes_at' => 'data limite',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(JobOpening $job, array $data): void
    {
        $job->fill(collect($data)->only(['slug', 'job_category_id', 'positions', 'experience', 'salary', 'cover_image_id', 'is_open', 'posted_at', 'closes_at'])->all());
        $job->posted_at ??= today();
        Translations::fill($job, $data, self::TRANSLATABLE);
        $job->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(JobOpening $job, bool $detailed = false): array
    {
        return [
            'id' => $job->id,
            'slug' => $job->slug,
            'title_text' => $job->getTranslation('title', config('site.default_locale')),
            'contract_text' => $job->getTranslation('contract_type', config('site.default_locale')),
            'category_text' => $job->category?->getTranslation('name', config('site.default_locale')),
            'location_text' => $job->getTranslation('location', config('site.default_locale')),
            'job_category_id' => $job->job_category_id,
            'positions' => $job->positions,
            'experience' => $job->experience,
            'salary' => $job->salary,
            'cover_image_id' => $job->cover_image_id,
            'is_open' => $job->is_open,
            'is_accepting' => $job->isOpen(),
            'posted_at' => $job->posted_at?->toDateString(),
            'closes_at' => $job->closes_at?->toDateString(),
            'applications_count' => $job->applications_count ?? 0,
            'sort_order' => $job->sort_order,
            'url' => $job->url(),
            'translation' => TranslationStatus::forApi($job),
            ...($detailed ? Translations::present($job) : []),
        ];
    }
}
