<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Corte de cabello', 'price' => 120, 'duration' => 40, 'category' => 'Corte',
                'image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600&q=80',
                'description' => 'Corte a tijera y/o máquina, incluye lavado y peinado final.'],
            ['name' => 'Arreglo de barba', 'price' => 90, 'duration' => 30, 'category' => 'Barba',
                'image' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=600&q=80',
                'description' => 'Perfilado, rasurado con navaja y toalla caliente.'],
            ['name' => 'Limpieza facial', 'price' => 80, 'duration' => 30, 'category' => 'Limpieza',
                'image' => 'https://images.unsplash.com/photo-1585232351009-aa87416fca90?w=600&q=80',
                'description' => 'Limpieza profunda, exfoliación y mascarilla relajante.'],
            ['name' => 'Corte de cabello, barba y bigote', 'price' => 220, 'duration' => 70, 'category' => 'Degradado',
                'image' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?w=600&q=80',
                'description' => 'Paquete completo: degradado, barba perfilada y bigote.'],
            ['name' => 'Degradado clásico', 'price' => 140, 'duration' => 45, 'category' => 'Degradado',
                'image' => 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=600&q=80',
                'description' => 'Fade clásico con acabado a navaja en contornos.'],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

        Service::factory()->count(7)->create();
    }
}
