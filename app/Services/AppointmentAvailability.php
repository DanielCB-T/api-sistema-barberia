<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
class AppointmentAvailability
{
    public static function hasConflict(
        int $barberId,
        string $start,
        int $durationMinutes,
        ?int $excludeAppointmentId = null,
    ): bool {
        $start = Carbon::parse($start);
        $end = $start->copy()->addMinutes($durationMinutes);

        return Appointment::query()
            ->where('barber_id', $barberId)
            ->where('status', '!=', 'cancelada')
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->where('date_time', '<', $end)
            ->whereRaw('DATE_ADD(date_time, INTERVAL duration MINUTE) > ?', [$start])
            ->exists();
    }
}
