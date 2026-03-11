<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
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
                'max:80',
                Rule::unique('tags', 'name')->where(
                    fn ($query) => $query->where('organization_id', $this->user()?->organization_id)
                ),
            ],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}

