<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'type' => ['required', 'string', 'in:error,lesson,best_practice'],
            'stack' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', 'string', 'in:project,global'],
            'official_reference' => ['nullable', 'url', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'title.max' => 'O título deve ter no máximo 500 caracteres.',
            'description.required' => 'A descrição é obrigatória.',
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'Tipo inválido. Valores permitidos: error, lesson, best_practice.',
            'scope.in' => 'Escopo inválido. Valores permitidos: project, global.',
            'official_reference.url' => 'A referência oficial deve ser uma URL válida.',
        ];
    }
}
