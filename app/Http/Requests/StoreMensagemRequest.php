<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMensagemRequest extends FormRequest
{
    /**
     * Autorização de quem pode enviar mensagem é responsabilidade dos
     * middlewares de guard dedicados (`EnsureAutorizadoEnviarMensagem`),
     * não deste FormRequest.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'texto' => ['required', 'string', 'max:4000'],
        ];
    }
}
