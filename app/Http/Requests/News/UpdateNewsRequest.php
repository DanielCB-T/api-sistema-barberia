<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'summary' => ['sometimes', 'required', 'string'],
            'date' => ['sometimes', 'required', 'date'],
            'image' => ['nullable', 'url'],
        ];
    }
}
