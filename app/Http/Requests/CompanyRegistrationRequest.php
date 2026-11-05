<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inscrição de empresa, com os campos do site antigo. O campo "website" é uma
 * armadilha para robôs.
 */
class CompanyRegistrationRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:160'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+()\s-]{6,}$/'],
            'email' => ['required', 'email', 'max:160'],
            'send_copy' => ['nullable', 'boolean'],
            'website' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => __('nome da empresa'),
            'name' => __('nome'),
            'phone' => __('telefone'),
            'email' => __('e-mail'),
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
