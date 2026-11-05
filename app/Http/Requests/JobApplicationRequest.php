<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Candidatura, com os campos do formulário do site antigo. O campo "website" é
 * uma armadilha para robôs.
 */
class JobApplicationRequest extends FormRequest
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
            'job_opening_id' => ['nullable', 'integer', Rule::in(JobOpening::open()->pluck('id')->all())],
            'name' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1930-01-01'],
            'gender' => ['required', Rule::in(JobApplication::GENDERS)],
            'document_type' => ['nullable', Rule::in(JobApplication::DOCUMENT_TYPES)],
            'document_number' => ['nullable', 'string', 'max:40'],
            'qualifications' => ['nullable', 'string', 'max:160'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'address' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+()\s-]{6,}$/'],
            'phone_alt' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\s-]{6,}$/'],
            'message' => ['nullable', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:'.JobApplication::CV_MAX_KB],
            'send_copy' => ['nullable', 'boolean'],
            'consent' => ['accepted'],
            'website' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'job_opening_id' => __('vaga'),
            'name' => __('nome completo'),
            'birth_date' => __('data de nascimento'),
            'gender' => __('sexo'),
            'document_type' => __('documento de registo'),
            'document_number' => __('número do documento'),
            'qualifications' => __('habilitações literárias'),
            'experience_years' => __('anos de experiência'),
            'address' => __('endereço'),
            'email' => __('e-mail'),
            'phone' => __('telefone'),
            'phone_alt' => __('telefone alternativo'),
            'message' => __('observações'),
            'cv' => __('currículo'),
            'consent' => __('autorização'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_opening_id.in' => __('Esta vaga já não está aberta. Escolha outra ou faça uma candidatura espontânea.'),
            'cv.mimes' => __('O currículo tem de ser um ficheiro PDF ou Word.'),
            'cv.max' => __('O currículo não pode ter mais de 20 MB.'),
            'cv.uploaded' => __('Não foi possível receber o currículo. Confirme que não tem mais de 20 MB.'),
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
