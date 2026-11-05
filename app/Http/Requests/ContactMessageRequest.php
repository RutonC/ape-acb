<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulário de contacto: nome completo, e-mail, assunto e mensagem, como no site antigo. O campo "website" é uma armadilha para robôs.
 */
class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:160'],
            'subject' => [$this->isQuick() ? 'nullable' : 'required', 'string', 'max:160'],
            'send_copy' => ['nullable', 'boolean'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => [$this->isQuick() ? 'required' : 'nullable', 'string', 'max:40', 'regex:/^[0-9+()\s-]{6,}$/'],
            'service' => ['nullable', 'string', Rule::in([...Service::published()->pluck('slug')->all(), 'outro', ...array_keys(config('site.contact_subjects'))])],
            'message' => $this->isQuick() ? ['nullable', 'string', 'max:5000'] : ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('nome'),
            'organization' => __('entidade'),
            'email' => __('e-mail'),
            'phone' => __('telefone'),
            'service' => __('serviço'),
            'subject' => __('assunto'),
            'message' => __('mensagem'),
        ];
    }

    /** Pedido de contacto rápido (bloco da página): sem mensagem, com telefone. */
    public function isQuick(): bool
    {
        return $this->boolean('quick');
    }

    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
