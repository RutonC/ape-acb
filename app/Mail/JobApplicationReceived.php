<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificação interna de nova candidatura. Não leva o CV nem dados de
 * identificação: esses vêem-se só no painel, com permissão.
 */
class JobApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public JobApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->application->email, $this->application->name)],
            subject: 'Nova candidatura: '.$this->application->positionLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.job-application');
    }
}
