<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InboxStatus;
use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\CompanyRegistration;
use App\Support\Admin\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Inscrições de empresas. Abrir uma inscrição nova marca-a como lida.
 */
class CompanyRegistrationController extends Controller
{
    use ManagesRecords;

    public function index(Request $request): JsonResponse
    {
        $registrations = ListQuery::for(CompanyRegistration::query(), $request)
            ->search(['company_name', 'name', 'email', 'phone'])
            ->filter('status', fn ($query, string $value) => $query->where('status', InboxStatus::tryFrom($value) ?? 'none'))
            ->sort(['created_at', 'company_name', 'status'], '-created_at')
            ->paginate(25);

        return response()->json([
            'data' => collect($registrations->items())->map(fn (CompanyRegistration $registration) => $this->present($registration))->values(),
            'meta' => ['current_page' => $registrations->currentPage(), 'per_page' => $registrations->perPage(), 'total' => $registrations->total(), 'last_page' => $registrations->lastPage()],
        ]);
    }

    public function show(CompanyRegistration $registration): JsonResponse
    {
        if ($registration->status === InboxStatus::New) {
            $registration->update(['status' => InboxStatus::Read]);
        }

        return response()->json(['data' => $this->present($registration)]);
    }

    public function update(Request $request, CompanyRegistration $registration): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(InboxStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['status' => 'estado', 'notes' => 'notas']);

        $registration->update($data);

        return response()->json(['data' => $this->present($registration->fresh())]);
    }

    public function destroy(CompanyRegistration $registration): JsonResponse
    {
        $registration->delete();

        return response()->json(status: 204);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        return $this->bulkDeleteRecords($request, CompanyRegistration::query());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(CompanyRegistration $registration): array
    {
        return [
            'id' => $registration->id,
            'company_name' => $registration->company_name,
            'name' => $registration->name,
            'phone' => $registration->phone,
            'email' => $registration->email,
            'status' => $registration->status->value,
            'status_label' => $registration->status->getLabel(),
            'notes' => $registration->notes,
            'created_at' => $registration->created_at?->toIso8601String(),
        ];
    }
}
