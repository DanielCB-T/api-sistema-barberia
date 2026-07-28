<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'birthdate' => ['sometimes', 'nullable', 'date'],
            'notify_whatsapp' => ['sometimes', 'boolean'],

            // El cambio de contraseña es opcional; si mandan "password"
            // también deben mandar la contraseña actual para confirmarla.
            'current_password' => ['required_with:password'],
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required_with' => 'Debes ingresar tu contraseña actual para cambiarla.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ];
    }

    /**
     * Verifica que "current_password" sea, en efecto, la contraseña actual
     * del usuario autenticado, antes de permitir el cambio.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('password')) {
                return;
            }

            if (! Hash::check($this->input('current_password'), $this->user()->password)) {
                $validator->errors()->add('current_password', 'La contraseña actual no es correcta.');
            }
        });
    }
}
