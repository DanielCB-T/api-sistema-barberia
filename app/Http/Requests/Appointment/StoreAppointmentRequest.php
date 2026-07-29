<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solo se usa cuando la crea un admin; un cliente siempre agenda
            // para sí mismo (el controlador ignora este campo si no eres admin).
            'client_id' => ['sometimes', 'integer', 'exists:users,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'barber_id' => ['required', 'integer', 'exists:users,id'],
            'date_time' => ['required', 'date', 'after:now'],
            'pay_online' => ['boolean'],
            'notify_whatsapp' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_time.after' => 'La fecha y hora de la cita debe ser en el futuro.',
            'barber_id.exists' => 'El barbero seleccionado no existe.',
            'client_id.exists' => 'El cliente seleccionado no existe.',
        ];
    }
}
