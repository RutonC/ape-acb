<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Quero receber uma cópia": confirma a recepção com texto fixo, sem nada do que
 * foi escrito no formulário, para o site não poder ser usado para enviar
 * mensagens a terceiros.
 */
class FormCopy extends Mailable
{
    use Queueable, SerializesModels;

    public const TEXTS = [
        'candidatura' => 'Recebemos a sua candidatura. A nossa equipa vai analisá-la e entra em contacto consigo se o seu perfil corresponder a uma vaga.',
        'empresa' => 'Recebemos a inscrição da sua empresa. A nossa equipa entra em contacto consigo em breve.',
        'contacto' => 'Recebemos a sua mensagem. A nossa equipa entra em contacto consigo em breve.',
    ];

    public function __construct(public string $kind) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'APE: confirmação de recepção');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.form-copy', with: ['text' => self::TEXTS[$this->kind]]);
    }
}
