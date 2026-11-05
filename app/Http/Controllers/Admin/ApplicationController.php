<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Support\Admin\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Candidaturas. Abrir uma candidatura nova passa-a a "Em análise". O CV está no
 * disco privado e só se descarrega por aqui; o documento de identificação e a data
 * de nascimento só aparecem no detalhe.
 */
class ApplicationController extends Controller
{
    use ManagesRecords;

    public function index(Request $request): JsonResponse
    {
        $applications = ListQuery::for(JobApplication::query()->with('jobOpening'), $request)
            ->search(['name', 'email', 'phone', 'qualifications', 'message'])
            ->filter('status', fn ($query, string $value) => $query->where('status', ApplicationStatus::tryFrom($value) ?? 'none'))
            ->filter('job', fn ($query, string $value) => $value === 'spontaneous'
                ? $query->whereNull('job_opening_id')
                : $query->where('job_opening_id', (int) $value))
            ->sort(['created_at', 'name', 'status'], '-created_at')
            ->paginate(25);

        return response()->json([
            'data' => collect($applications->items())->map(fn (JobApplication $application) => $this->present($application))->values(),
            'meta' => ['current_page' => $applications->currentPage(), 'per_page' => $applications->perPage(), 'total' => $applications->total(), 'last_page' => $applications->lastPage()],
        ]);
    }

    public function show(JobApplication $application): JsonResponse
    {
        if ($application->status === ApplicationStatus::New) {
            $application->update(['status' => ApplicationStatus::Reviewing]);
        }

        return response()->json(['data' => $this->present($application, detailed: true)]);
    }

    public function update(Request $request, JobApplication $application): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['status' => 'estado', 'notes' => 'notas']);

        $application->update($data);

        return response()->json(['data' => $this->present($application->fresh(), detailed: true)]);
    }

    public function cv(JobApplication $application): StreamedResponse
    {
        abort_unless(Storage::disk(JobApplication::CV_DISK)->exists($application->cv_path), 404, 'O ficheiro do currículo já não existe.');

        return Storage::disk(JobApplication::CV_DISK)->download($application->cv_path, $application->cv_original_name);
    }

    public function destroy(JobApplication $application): JsonResponse
    {
        $application->delete();

        return response()->json(status: 204);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        // Um a um, para o evento "deleted" apagar também cada CV.
        return $this->bulkDeleteRecords($request, JobApplication::query());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(JobApplication $application, bool $detailed = false): array
    {
        return [
            'id' => $application->id,
            'name' => $application->name,
            'email' => $application->email,
            'phone' => $application->phone,
            'position' => $application->positionLabel(),
            'job_opening_id' => $application->job_opening_id,
            'cv_name' => $application->cv_original_name,
            'locale' => $application->locale,
            'status' => $application->status->value,
            'status_label' => $application->status->getLabel(),
            'created_at' => $application->created_at?->toIso8601String(),
            ...($detailed ? [
                'birth_date' => $application->birth_date?->toDateString(),
                'gender' => $application->gender,
                'document_type' => $application->document_type,
                'document_number' => $application->document_number,
                'qualifications' => $application->qualifications,
                'experience_years' => $application->experience_years,
                'address' => $application->address,
                'phone_alt' => $application->phone_alt,
                'message' => $application->message,
                'notes' => $application->notes,
            ] : []),
        ];
    }
}
