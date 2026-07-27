<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reprogramación de una cita (cambiar fecha/hora, servicio o barbero).
 * El cambio de estado (aceptar, posponer, cancelar...) va aparte, en
 * UpdateAppointmentStatusRequest, para no mezclar las dos operaciones.
 */
class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['sometimes', 'required', 'integer', 'exists:services,id'],
            'branch_id' => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            'barber_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'date_time' => ['sometimes', 'required', 'date', 'after:now'],
        ];
    }
}
