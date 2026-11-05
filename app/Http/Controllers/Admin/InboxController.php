<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\InboxStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyRegistration;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contadores da Caixa de entrada para o menu lateral: só das áreas
 * a que o utilizador tem acesso.
 */
class InboxController extends Controller
{
    public function counts(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'messages' => $user->can('messages') ? ContactMessage::where('status', InboxStatus::New)->count() : 0,
                'applications' => $user->can('applications') ? JobApplication::where('status', ApplicationStatus::New)->count() : 0,
                'companies' => $user->can('companies') ? CompanyRegistration::where('status', InboxStatus::New)->count() : 0,
            ],
        ]);
    }
}
