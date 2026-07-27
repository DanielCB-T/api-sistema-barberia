<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
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

    public static function isWithinBusinessHours(
        Branch $branch,
        string $start,
        int $durationMinutes,
    ): bool {
        $start = Carbon::parse($start);
        $end = $start->copy()->addMinutes($durationMinutes);

        $opening = $start->copy()->setTimeFrom($branch->opening_time);
        $closing = $start->copy()->setTimeFrom($branch->closing_time);

        return $start->gte($opening) && $end->lte($closing);
    }
}
