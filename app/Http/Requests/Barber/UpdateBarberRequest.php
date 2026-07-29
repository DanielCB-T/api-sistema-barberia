<?php

namespace App\Http\Requests\Barber;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateBarberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barberId = $this->route('barber');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($barberId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'branch_id' => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            // La contraseña es opcional al editar: solo se cambia si se envía.
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
