<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->first_name ? strip_tags($this->first_name) : null,
            'last_name'  => $this->last_name  ? strip_tags($this->last_name)  : null,
            'profession' => $this->profession  ? strip_tags($this->profession) : null,
            'biography'  => $this->biography   ? strip_tags($this->biography)  : null,
            'phone'      => $this->phone       ? strip_tags($this->phone)      : null,
            'location'   => $this->location    ? strip_tags($this->location)   : null,
            'linkedin_url' => $this->linkedin_url ? strip_tags($this->linkedin_url) : null,
            'github_url'   => $this->github_url   ? strip_tags($this->github_url)   : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'min:1', 'max:50'],
            'last_name'  => ['sometimes', 'string', 'min:1', 'max:50'],
            'profession' => ['nullable', 'string', 'max:255'],
            'biography'  => ['nullable', 'string', 'max:2000'],
            'phone'      => ['nullable', 'string', 'max:100'],
            'location'   => ['nullable', 'string', 'max:255'],
            'linkedin_url' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    $host = parse_url($value, PHP_URL_HOST) ?? '';
                    if (! str_contains($host, 'linkedin.com')) {
                        $fail('The LinkedIn URL must be a valid linkedin.com URL.');
                    }
                },
            ],
            'github_url' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    $host = parse_url($value, PHP_URL_HOST) ?? '';
                    if (! str_contains($host, 'github.com')) {
                        $fail('The GitHub URL must be a valid github.com URL.');
                    }
                },
            ],
            'global_privacy' => ['sometimes', 'in:public,private'],
            'show_projects' => ['sometimes', 'boolean'],
            'show_skills' => ['sometimes', 'boolean'],
            'show_experience' => ['sometimes', 'boolean'],
            'show_certifications' => ['sometimes', 'boolean'],
        ];
    }
}
