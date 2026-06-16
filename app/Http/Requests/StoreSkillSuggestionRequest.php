<?php

namespace App\Http\Requests;

use App\Models\Skill;
use App\Models\SkillSuggestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSkillSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'          => ['required', 'string', 'in:tecnica,blanda'],
            'category'      => ['required', 'string', 'max:100'],
            'name'          => ['required', 'string', 'max:100'],
            'level'         => ['required', 'string', 'in:basico,intermedio,avanzado,en_formacion,desarrollada,fortalecida'],
            'justification' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Validate level matches the type
            if ($this->type === 'tecnica' && ! in_array($this->level, ['basico', 'intermedio', 'avanzado'])) {
                $validator->errors()->add('level', 'El nivel para habilidades técnicas debe ser: basico, intermedio, avanzado.');
            }

            if ($this->type === 'blanda' && ! in_array($this->level, ['en_formacion', 'desarrollada', 'fortalecida'])) {
                $validator->errors()->add('level', 'El nivel para habilidades blandas debe ser: en_formacion, desarrollada, fortalecida.');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Category must exist for the given type
            $categoryExists = Skill::where('type', $this->type)
                ->where('category', $this->category)
                ->exists();

            if (! $categoryExists) {
                $validator->errors()->add('category', 'La categoría no existe para el tipo seleccionado.');
            }

            // Skill name must not already exist in that category (check with fuzzy matching)
            $suggestedName = strtolower(trim($this->name));
            $existingSkills = Skill::where('category', $this->category)->get(['name']);
            
            foreach ($existingSkills as $skill) {
                $existingName = strtolower(trim($skill->name));
                
                if ($existingName === $suggestedName) {
                    $validator->errors()->add('name', 'Esta habilidad ya existe en el catálogo. Agrégala directamente desde el catálogo.');
                    break;
                }
                
                // Levenshtein distance: allow up to 2 typos for relatively long words
                if (strlen($suggestedName) > 3 && levenshtein($existingName, $suggestedName) <= 2) {
                    $validator->errors()->add('name', "La habilidad sugerida es muy similar a '{$skill->name}', que ya existe en el catálogo.");
                    break;
                }
            }

            // User must not have a pending suggestion with the same name in the same category
            $portfolio = $this->user()->portfolio;
            if ($portfolio) {
                $duplicateSuggestion = SkillSuggestion::where('portfolio_id', $portfolio->id)
                    ->where('status', 'pending')
                    ->where('category', $this->category)
                    ->whereRaw('LOWER(name) = ?', [strtolower($this->name)])
                    ->exists();

                if ($duplicateSuggestion) {
                    $validator->errors()->add('name', 'Ya tienes una sugerencia pendiente con este nombre en esa categoría.');
                }
            }
        });
    }
}
