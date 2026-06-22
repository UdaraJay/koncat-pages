<?php

namespace App\Http\Requests\Teams;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamBrandingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'file', 'image', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'brand_background_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_foreground_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'brand_background_color' => $this->normalizeColor($this->input('brand_background_color')),
            'brand_foreground_color' => $this->normalizeColor($this->input('brand_foreground_color')),
        ]);
    }

    protected function normalizeColor(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $color = trim((string) $value);

        return $color === '' ? null : strtolower($color);
    }
}
