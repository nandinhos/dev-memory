<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'type' => ['sometimes', 'string', 'in:error,lesson,best_practice'],
            'stack' => ['nullable', 'string', 'max:100'],
            'scope' => ['sometimes', 'string', 'in:project,global'],
            'validation_status' => ['sometimes', 'string', 'in:pending,validated,rejected'],
            'official_reference' => ['nullable', 'url'],
            'recurrence_count' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'type.in' => 'O tipo deve ser: error, lesson ou best_practice.',
            'scope.in' => 'O escopo deve ser: project ou global.',
            'validation_status.in' => 'O status deve ser: pending, validated ou rejected.',
            'official_reference.url' => 'A referência oficial deve ser uma URL válida.',
        ];
    }
}
