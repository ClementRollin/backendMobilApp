<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:140',
                Rule::unique('teams', 'name')->where(
                    fn ($query) => $query->where('organization_id', $this->user()?->organization_id)
                ),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

