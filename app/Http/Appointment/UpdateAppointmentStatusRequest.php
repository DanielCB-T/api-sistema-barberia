<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['pendiente', 'confirmada', 'pospuesta', 'completada', 'cancelada']),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
