<?php

namespace App\Http\Requests;

use App\Enums\PortfolioTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'design_pattern' => [
                'required',
                'string',
                Rule::in(PortfolioTheme::values()),
            ],
        ];
    }

    public function messages(): array
    {
        $valid = implode(', ', PortfolioTheme::values());

        return [
            'design_pattern.required' => 'El campo design_pattern es obligatorio.',
            'design_pattern.in'       => "El tema seleccionado no es válido. Opciones disponibles: {$valid}.",
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('design_pattern')) {
            $this->merge([
                'design_pattern' => strip_tags($this->design_pattern),
            ]);
        }
    }
}