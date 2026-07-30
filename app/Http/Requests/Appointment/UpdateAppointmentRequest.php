<?php

namespace App\Http\Requests\Appointment;

use App\Models\Branch;
use App\Models\Service;
use App\Services\AppointmentAvailability;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['service_id', 'branch_id', 'barber_id', 'date_time'])) {
                return;
            }

            if (! $this->hasAny(['service_id', 'branch_id', 'barber_id', 'date_time'])) {
                return;
            }

            $appointment = $this->route('appointment');

            $barberId = (int) ($this->input('barber_id') ?? $appointment->barber_id);
            $dateTime = $this->input('date_time') ?? $appointment->date_time;
            $serviceId = $this->input('service_id') ?? $appointment->service_id;
            $branchId = $this->input('branch_id') ?? $appointment->branch_id;

            $service = Service::find($serviceId);
            $branch = Branch::find($branchId);
            if (! $service || ! $branch) {
                return;
            }

            if (! AppointmentAvailability::isWithinBusinessHours(
                branch: $branch,
                start: (string) $dateTime,
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
                barberId: $barberId,
                start: (string) $dateTime,
                durationMinutes: $service->duration,
                excludeAppointmentId: $appointment->id,
            )) {
                $validator->errors()->add(
                    'barber_id',
                    'El barbero ya tiene una cita agendada en ese horario. Elige otro horario o barbero.'
                );
            }
        });
    }
}
