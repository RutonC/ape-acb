<?php

namespace App\Models;

use App\Enums\InboxStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Mensagem do formulário de contacto (RF02).
 *
 * @property InboxStatus $status
 */
#[Fillable(['name', 'organization', 'email', 'phone', 'service', 'subject', 'message', 'locale', 'status', 'notes'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => InboxStatus::class,
        ];
    }

    /**
     * Nome do serviço escolhido (o formulário guarda o endereço do serviço ou "outro").
     */
    public function serviceLabel(): ?string
    {
        if (blank($this->service)) {
            return null;
        }

        if ($this->service === 'outro') {
            return 'Outro assunto';
        }

        if ($subject = config("site.contact_subjects.{$this->service}")) {
            return $subject;
        }

        $service = Service::where('slug', $this->service)->first();

        return $service?->getTranslation('title', config('site.default_locale')) ?? $this->service;
    }
}
