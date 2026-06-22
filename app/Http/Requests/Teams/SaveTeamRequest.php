<?php

namespace App\Http\Requests\Teams;

use App\Rules\TeamName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('team') ?? $this->route('current_team');

        return [
            'name' => ['required', 'string', 'max:255', new TeamName],
            'subdomain' => [
                $this->isMethod('post') ? 'nullable' : 'required',
                'string',
                'min:1',
                'max:63',
                'lowercase',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn(['www', 'app', 'api', 'admin', 'mail']),
                Rule::unique('teams', 'subdomain')->ignore($team),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('subdomain')) {
            $this->merge([
                'subdomain' => strtolower(trim((string) $this->input('subdomain'))),
            ]);
        }
    }
}
