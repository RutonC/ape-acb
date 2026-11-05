<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InboxStatus;
use App\Http\Controllers\Admin\Concerns\ManagesRecords;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\Admin\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Mensagens do formulário de contacto (RF02). Abrir uma mensagem nova marca-a como lida.
 */
class MessageController extends Controller
{
    use ManagesRecords;

    public function index(Request $request): JsonResponse
    {
        $messages = ListQuery::for(ContactMessage::query(), $request)
            ->search(['name', 'organization', 'email', 'phone', 'subject', 'message'])
            ->filter('status', fn ($query, string $value) => $query->where('status', InboxStatus::tryFrom($value) ?? 'none'))
            ->sort(['created_at', 'name', 'status'], '-created_at')
            ->paginate(25);

        return response()->json([
            'data' => collect($messages->items())->map(fn (ContactMessage $message) => $this->present($message))->values(),
            'meta' => ['current_page' => $messages->currentPage(), 'per_page' => $messages->perPage(), 'total' => $messages->total(), 'last_page' => $messages->lastPage()],
        ]);
    }

    public function show(ContactMessage $message): JsonResponse
    {
        if ($message->status === InboxStatus::New) {
            $message->update(['status' => InboxStatus::Read]);
        }

        return response()->json(['data' => $this->present($message, detailed: true)]);
    }

    public function update(Request $request, ContactMessage $message): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(InboxStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['status' => 'estado', 'notes' => 'notas']);

        $message->update($data);

        return response()->json(['data' => $this->present($message->fresh(), detailed: true)]);
    }

    public function destroy(ContactMessage $message): JsonResponse
    {
        $message->delete();

        return response()->json(status: 204);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        return $this->bulkDeleteRecords($request, ContactMessage::query());
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::enum(InboxStatus::class)],
        ]);

        ContactMessage::whereKey($data['ids'])->update(['status' => $data['status']]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ContactMessage $message, bool $detailed = false): array
    {
        return [
            'id' => $message->id,
            'name' => $message->name,
            'organization' => $message->organization,
            'email' => $message->email,
            'phone' => $message->phone,
            'service' => $message->serviceLabel(),
            'subject' => $message->subject,
            'excerpt' => str($message->message)->limit(110)->toString(),
            'locale' => $message->locale,
            'status' => $message->status->value,
            'status_label' => $message->status->getLabel(),
            'created_at' => $message->created_at?->toIso8601String(),
            ...($detailed ? ['message' => $message->message, 'notes' => $message->notes] : []),
        ];
    }
}
