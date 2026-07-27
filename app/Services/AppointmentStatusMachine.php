<?php

namespace App\Services;

/**
 * Máquina de estados de una cita.
 * pendiente -> confirmada | pospuesta | cancelada
 * confirmada -> completada | pospuesta | cancelada
 * pospuesta -> confirmada | cancelada
 * completada -> (estado final, sin transiciones)
 * cancelada -> (estado final, sin transiciones)
 */
class AppointmentStatusMachine
{
    private const TRANSITIONS = [
        'pendiente' => ['confirmada', 'pospuesta', 'cancelada'],
        'confirmada' => ['completada', 'pospuesta', 'cancelada'],
        'pospuesta' => ['confirmada', 'cancelada'],
        'completada' => [],
        'cancelada' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function allowedFrom(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }
}
