<?php

namespace App\Http\Requests;

use App\Models\CategorySuggestion;
use App\Models\ProjectCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCategorySuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name ? strip_tags($this->name) : $this->name,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'justification' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Name must not already exist in project_categories (check with fuzzy matching)
            $suggestedName = strtolower(trim($this->name));
            $existingCategories = ProjectCategory::get(['name']);
            
            foreach ($existingCategories as $category) {
                $existingName = strtolower(trim($category->name));
                
                if ($existingName === $suggestedName) {
                    $validator->errors()->add('name', 'Esta categoría ya existe. Selecciónala directamente de la lista.');
                    break;
                }
                
                // Levenshtein distance: allow up to 2 typos for relatively long words
                if (strlen($suggestedName) > 3 && levenshtein($existingName, $suggestedName) <= 2) {
                    $validator->errors()->add('name', "La categoría sugerida es muy similar a '{$category->name}', que ya existe.");
                    break;
                }
            }

            // Name must not have another pending suggestion already
            $pendingNameExists = CategorySuggestion::where('status', 'pending')
                ->whereRaw('LOWER(name) = ?', [strtolower($this->name)])
                ->exists();
            if ($pendingNameExists) {
                $validator->errors()->add('name', 'There is already a pending suggestion with this category name.');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Project can only have ONE pending suggestion at a time
            $project = $this->route('project');
            if ($project) {
                $projectHasPending = CategorySuggestion::where('project_id', $project->id)
                    ->where('status', 'pending')
                    ->exists();
                if ($projectHasPending) {
                    $validator->errors()->add('project', 'This project already has a pending category suggestion. Wait for it to be reviewed before submitting a new one.');
                }
            }
        });
    }
}
