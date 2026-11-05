<?php

namespace App\Http\Controllers;

use App\Enums\InboxStatus;
use App\Http\Requests\CompanyRegistrationRequest;
use App\Mail\CompanyRegistrationReceived;
use App\Mail\FormCopy;
use App\Models\CompanyRegistration;
use App\Models\Page;
use App\Support\CompanyProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Página para as empresas e formulário "Inscrever minha empresa".
 */
class CompanyController extends Controller
{
    public function show(): View
    {
        $page = Page::published()->where('slug', 'empresas')->first();

        return view('companies.show', [
            'page' => $page,
            'blocks' => $page?->blocks() ?? [],
        ]);
    }

    public function store(CompanyRegistrationRequest $request): RedirectResponse
    {
        if (! $request->isSpam()) {
            $registration = CompanyRegistration::create([
                ...$request->safe()->except(['website', 'send_copy']),
                'status' => InboxStatus::New,
            ]);

            $this->notify($registration, $request->boolean('send_copy'));
        }

        return redirect()->to(lroute('companies').'#inscricao')->with('company_sent', true);
    }

    private function notify(CompanyRegistration $registration, bool $sendCopy): void
    {
        $recipient = app(CompanyProfile::class)->toArray()['forms_recipient_email'];

        try {
            if (filled($recipient)) {
                Mail::to($recipient)->send(new CompanyRegistrationReceived($registration));
            }

            if ($sendCopy) {
                Mail::to($registration->email)->send(new FormCopy('empresa'));
            }
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar notificação de inscrição de empresa', ['id' => $registration->id, 'erro' => $exception->getMessage()]);
        }
    }
}
