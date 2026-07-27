<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'category' => ['sometimes', 'required', 'string', 'max:80'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'duration' => ['sometimes', 'required', 'integer', 'min:5', 'max:480'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'url'],
        ];
    }
}
