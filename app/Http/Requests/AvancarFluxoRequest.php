<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AvancarFluxoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Só valida o shape genérico do envelope — o significado de `opcao` é
     * decidido pela definição do fluxo (`AvancarFluxoService`), não aqui.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opcao' => ['required', 'string'],
        ];
    }
}
