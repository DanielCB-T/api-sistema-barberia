<?php

namespace App\Http\Requests\Appointment;

use App\Models\Branch;
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
            // Solo se usa cuando la crea un admin; un cliente siempre agenda
            // para sí mismo (el controlador ignora este campo si no eres admin).
            'client_id' => ['sometimes', 'integer', 'exists:users,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'barber_id' => ['required', 'integer', 'exists:users,id'],
            'date_time' => ['required', 'date', 'after:now'],
            'pay_online' => ['boolean'],
            'notify_sms' => ['boolean'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['service_id', 'branch_id', 'barber_id', 'date_time'])) {
                return;
            }

            $service = Service::find($this->input('service_id'));
            $branch = Branch::find($this->input('branch_id'));
            if (! $service || ! $branch) {
                return;
            }

            if (! AppointmentAvailability::isWithinBusinessHours(
                branch: $branch,
                start: $this->input('date_time'),
                durationMinutes: $service->duration,
            )) {
                $validator->errors()->add(
                    'date_time',
                    sprintf(
                        'Esta sucursal atiende de %s a %s. Elige un horario dentro de ese rango.',
                        $branch->opening_time->format('H:i'),
                        $branch->closing_time->format('H:i'),
                    )
                );

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
