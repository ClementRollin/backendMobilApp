<?php

namespace App\Http\Requests\Tag;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Tag|null $tag */
        $tag = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('tags', 'name')
                    ->where(fn ($query) => $query->where('organization_id', $this->user()?->organization_id))
                    ->ignore($tag?->id),
            ],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}

