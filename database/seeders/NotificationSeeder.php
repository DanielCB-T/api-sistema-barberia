<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $appointments = Appointment::with('client')->get();

        foreach ($appointments->random(min(12, $appointments->count())) as $appointment) {
            Notification::create([
                'user_id' => $appointment->client_id,
                'appointment_id' => $appointment->id,
                'channel' => fake()->randomElement(['email', 'sms']),
                'message' => "Tu cita del servicio ha sido actualizada a estado: {$appointment->status}.",
                'status' => fake()->randomElement(['enviado', 'enviado', 'enviado', 'fallido']),
            ]);
        }
    }
}
