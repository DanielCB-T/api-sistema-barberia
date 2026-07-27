<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * El orden respeta las llaves foráneas: primero las sucursales (los
     * barberos las necesitan), luego usuarios, catálogo, y al final lo que
     * depende de todo lo anterior (citas, pedidos, notificaciones).
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
            ServiceSeeder::class,
            ProductSeeder::class,
            NewsSeeder::class,
            AppointmentSeeder::class,
            OrderSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
