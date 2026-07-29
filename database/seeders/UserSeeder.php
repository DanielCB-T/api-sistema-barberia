<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        // --- Cuenta fija de evaluación (rúbrica: "usuario developer") ---
        User::create([
            'name' => 'Developer',
            'email' => 'developer@barberia.com',
            'password' => Hash::make('Developer123!'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'phone' => '+52 951 000 0000',
            'birthdate' => '1995-01-01',
        ]);

        // --- Admin de negocio (dato ya existente en el frontend actual) ---
        User::create([
            'name' => 'Jose Daniel',
            'email' => 'admin@barberia.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'phone' => '+52 951 000 0001',
            'birthdate' => '1990-01-01',
            'avatar' => 'https://i.pravatar.cc/150?img=12',
        ]);

        // --- Cliente demo (dato ya existente en el frontend actual) ---
        User::create([
            'name' => 'David Hernández',
            'email' => 'user@barberia.com',
            'password' => Hash::make('Client123!'),
            'role' => 'client',
            'email_verified_at' => now(),
            'phone' => '+52 951 222 3344',
            'birthdate' => '1998-05-20',
            'avatar' => 'https://i.pravatar.cc/150?img=33',
        ]);

        // --- Barberos: uno por sucursal como mínimo ---
        $barberNames = ['Carlos Ruiz', 'Miguel Ángel Torres', 'Fernando López', 'Luis Martínez', 'Ricardo Gómez'];
        foreach ($branches as $index => $branch) {
            $name = $barberNames[$index] ?? fake()->name('male');

            User::create([
                'name' => $name,
                'email' => 'barbero'.($index + 1).'@barberia.com',
                'password' => Hash::make('Barbero123!'),
                'role' => 'barber',
                'email_verified_at' => now(),
                'branch_id' => $branch->id,
                'phone' => fake()->numerify('+52 951 ### ####'),
                'birthdate' => fake()->dateTimeBetween('-45 years', '-20 years')->format('Y-m-d'),
            ]);
        }

        // --- Clientes de relleno para tener volumen de datos ---
        User::factory()->count(10)->create();
    }
}
