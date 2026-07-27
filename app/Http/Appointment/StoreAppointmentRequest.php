<?php

namespace App\Http\Requests\Appointment;

use App\Models\Service;
use App\Services\AppointmentAvailability;
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
        ];
    }

    /**
     * Tras pasar las reglas básicas, valida que el barbero elegido esté
     * libre en ese horario (considerando la duración del servicio).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['service_id', 'barber_id', 'date_time'])) {
                return;
            }

            $service = Service::find($this->input('service_id'));
            if (! $service) {
                return;
            }

            if (AppointmentAvailability::hasConflict(
                barberId: (int) $this->input('barber_id'),
                start: $this->input('date_time'),
                durationMinutes: $service->duration,
            )) {
                $validator->errors()->add(
                    'barber_id',
                    'El barbero ya tiene una cita agendada en ese horario. Elige otro horario o barbero.'
                );
            }
        });
    }
}
