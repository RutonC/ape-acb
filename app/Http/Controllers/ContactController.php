<?php

namespace App\Http\Controllers;

use App\Enums\InboxStatus;
use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Mail\FormCopy;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Support\CompanyProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Página de contactos e formulário (RF02). As mensagens ficam na Caixa de
 * entrada do painel e são enviadas por e-mail para o destinatário definido.
 */
class ContactController extends Controller
{
    public function show(): View
    {
        $page = Page::published()->where('slug', 'contactos')->first();

        return view('contact.show', [
            'page' => $page,
            'blocks' => $page?->blocks() ?? [],
        ]);
    }

    public function store(ContactMessageRequest $request): RedirectResponse
    {
        // Robôs que preenchem a armadilha recebem a mesma resposta, sem nada ser guardado.
        if (! $request->isSpam()) {
            $message = ContactMessage::create([
                ...$request->safe()->except(['website', 'send_copy']),
                'message' => $request->validated('message') ?: 'Pedido de contacto através do site.',
                'locale' => app()->getLocale(),
                'status' => InboxStatus::New,
            ]);

            $this->notify($message, $request->boolean('send_copy'));
        }

        // O pedido rápido regressa à página onde está o formulário.
        $target = $request->isQuick()
            ? url()->previous().(preg_match('/^#[a-z0-9-]+$/', (string) $request->input('return_to')) ? $request->input('return_to') : '')
            : lroute('contact').'#formulario';

        return redirect()
            ->to($target)
            ->with('contact_sent', true);
    }

    private function notify(ContactMessage $message, bool $sendCopy): void
    {
        $recipient = app(CompanyProfile::class)->toArray()['forms_recipient_email'];

        try {
            // Sem destinatário definido, a mensagem fica só na Caixa de entrada.
            if (filled($recipient)) {
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            }

            if ($sendCopy) {
                Mail::to($message->email)->send(new FormCopy('contacto'));
            }
        } catch (Throwable $exception) {
            // A mensagem já está guardada no painel; uma falha de e-mail não deve perdê-la.
            Log::error('Falha ao enviar notificação de contacto', ['id' => $message->id, 'erro' => $exception->getMessage()]);
        }
    }
}
