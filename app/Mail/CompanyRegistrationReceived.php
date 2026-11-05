<?php

namespace App\Mail;

use App\Models\CompanyRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificação interna de nova inscrição de empresa.
 */
class CompanyRegistrationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->registration->email, $this->registration->name)],
            subject: 'Nova inscrição de empresa: '.$this->registration->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.company-registration');
    }
}
