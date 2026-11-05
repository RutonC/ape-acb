<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Support\Admin\ListQuery;
use App\Support\Admin\Translations;
use App\Support\TranslationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Equipa (bloco "Equipa" das páginas institucionais).
 */
class TeamMemberController extends Controller
{
    use ManagesRecords;

    public function index(Request $request): JsonResponse
    {
        $members = ListQuery::for(TeamMember::query(), $request)
            ->search(['name', 'role->pt'])
            ->filter('published', fn ($query, string $value) => $query->where('is_published', $value === '1'))
            ->sort(['sort_order', 'name'], 'sort_order')
            ->paginate(50);

        return response()->json([
            'data' => collect($members->items())->map(fn (TeamMember $member) => $this->present($member))->values(),
            'meta' => ['current_page' => $members->currentPage(), 'per_page' => $members->perPage(), 'total' => $members->total(), 'last_page' => $members->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $member = new TeamMember(['sort_order' => $this->nextSortOrder(TeamMember::query())]);
        $this->save($member, $this->validated($request));

        return response()->json(['data' => $this->present($member)], 201);
    }

    public function update(Request $request, TeamMember $teamMember): JsonResponse
    {
        $this->save($teamMember, $this->validated($request));

        return response()->json(['data' => $this->present($teamMember->fresh())]);
    }

    public function destroy(TeamMember $teamMember): JsonResponse
    {
        $teamMember->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->reorderRecords($request, TeamMember::query());
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'photo_id' => ['nullable', 'integer', Rule::exists('curator', 'id')],
            'linkedin_url' => ['nullable', 'url:https,http', 'max:255'],
            'email' => ['nullable', 'email', 'max:190'],
            'is_published' => ['boolean'],
            ...Translations::rules('role', ['string', 'max:120'], required: true),
            ...Translations::rules('bio', ['string', 'max:300']),
        ], [], [...Translations::attributes(['role' => 'função', 'bio' => 'especialidade']), 'name' => 'nome', 'linkedin_url' => 'LinkedIn']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(TeamMember $member, array $data): void
    {
        $member->fill(collect($data)->only(['name', 'photo_id', 'linkedin_url', 'email', 'is_published'])->all());
        Translations::fill($member, $data, ['role', 'bio']);
        $member->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(TeamMember $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'role_text' => $member->getTranslation('role', config('site.default_locale')),
            ...Translations::present($member),
            'photo_id' => $member->photo_id,
            'linkedin_url' => $member->linkedin_url,
            'email' => $member->email,
            'is_published' => $member->is_published,
            'sort_order' => $member->sort_order,
            'translation' => TranslationStatus::forApi($member),
        ];
    }
}
