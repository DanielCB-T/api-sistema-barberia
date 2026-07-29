<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();
        $services = Service::all();
        $statuses = ['pendiente', 'confirmada', 'pospuesta', 'completada', 'cancelada'];

        for ($i = 0; $i < 15; $i++) {
            $client = $clients->random();
            $service = $services->random();

            // El barbero asignado debe pertenecer a una sucursal (así la cita
            // hereda esa misma sucursal, respetando la regla de negocio).
            $barber = User::where('role', 'barber')->inRandomOrder()->first();
            $status = $statuses[array_rand($statuses)];

            $appointment = Appointment::create([
                'client_id' => $client->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'branch_id' => $barber->branch_id,
                'date_time' => now()->addDays(rand(-10, 20))->setTime(rand(10, 19), [0, 30][rand(0, 1)]),
                'duration' => $service->duration,
                'status' => $status,
                'pay_online' => (bool) rand(0, 1),
                'notify_whatsapp' => true,
            ]);

            AppointmentStatusHistory::create([
                'appointment_id' => $appointment->id,
                'status' => 'pendiente',
                'changed_by' => $client->id,
                'note' => 'Cita creada por el cliente.',
            ]);

            if ($status !== 'pendiente') {
                AppointmentStatusHistory::create([
                    'appointment_id' => $appointment->id,
                    'status' => $status,
                    'changed_by' => $barber->id,
                    'note' => "Actualizada a {$status} por el barbero.",
                ]);
            }
        }
    }
}
