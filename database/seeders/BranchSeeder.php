<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Barbería Centro',
                'address' => 'Av. Independencia 501, Centro, Oaxaca de Juárez',
                'phone' => '+52 951 123 4567',
                'opening_time' => '10:00:00',
                'closing_time' => '20:00:00',
                'image' => 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=700&q=80',
            ],
            [
                'name' => 'Barbería Reforma',
                'address' => 'Calz. Madero 220, Reforma, Oaxaca de Juárez',
                'phone' => '+52 951 765 4321',
                'opening_time' => '09:00:00',
                'closing_time' => '21:00:00',
                'image' => 'https://images.unsplash.com/photo-1622286342621-4bd786c2447c?w=700&q=80',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }

        // Sucursales adicionales de relleno.
        Branch::factory()->count(3)->create();
    }
}
