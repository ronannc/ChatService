<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusSistema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSistemaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'codigo' => ['required', 'string', 'max:255', 'unique:sistemas,codigo'],
            'nome' => ['required', 'string', 'max:255'],
            'jwks_url' => ['required', 'url:https', 'max:2048'],
            'status' => ['sometimes', new Enum(StatusSistema::class)],
        ];
    }
}
