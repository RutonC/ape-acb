<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\JobCategory;
use App\Support\Admin\Translations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Áreas de actividade das vagas.
 */
class JobCategoryController extends Controller
{
    use ManagesRecords;

    public function index(): JsonResponse
    {
        $categories = JobCategory::query()->withCount('jobs')->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['data' => $categories->map(fn (JobCategory $category) => $this->present($category))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $category = new JobCategory(['sort_order' => $this->nextSortOrder(JobCategory::query())]);
        $this->save($category, $this->validated($request));

        return response()->json(['data' => $this->present($category->loadCount('jobs'))], 201);
    }

    public function update(Request $request, JobCategory $category): JsonResponse
    {
        $this->save($category, $this->validated($request, $category));

        return response()->json(['data' => $this->present($category->fresh()->loadCount('jobs'))]);
    }

    public function destroy(JobCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->reorderRecords($request, JobCategory::query());
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?JobCategory $category = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('job_categories', 'slug')->ignore($category?->id)],
            'icon' => ['nullable', 'string', 'max:60'],
            ...Translations::rules('name', ['string', 'max:80'], required: true),
        ], [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífenes.',
            'slug.unique' => 'Já existe uma categoria com este endereço.',
        ], Translations::attributes(['name' => 'nome']));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(JobCategory $category, array $data): void
    {
        $category->fill(collect($data)->only(['slug', 'icon'])->all());
        Translations::fill($category, $data, ['name']);
        $category->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(JobCategory $category): array
    {
        return [
            'id' => $category->id,
            'slug' => $category->slug,
            'icon' => $category->icon,
            'name_text' => $category->getTranslation('name', config('site.default_locale')),
            'jobs_count' => $category->jobs_count ?? 0,
            'sort_order' => $category->sort_order,
            ...Translations::present($category),
        ];
    }
}
