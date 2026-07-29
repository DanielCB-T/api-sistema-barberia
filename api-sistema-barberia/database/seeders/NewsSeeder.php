<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $news = [
            ['title' => '¡Nueva sucursal en Reforma!', 'date' => '2026-06-15',
                'image' => 'https://images.unsplash.com/photo-1622287162716-f311baa1a2b8?w=700&q=80',
                'summary' => 'Abrimos una nueva sucursal con más horarios disponibles para ti.'],
            ['title' => 'Promoción de temporada', 'date' => '2026-07-01',
                'image' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?w=700&q=80',
                'summary' => '20% de descuento en el paquete de corte + barba durante julio.'],
            ['title' => 'Nuevos productos de cuidado', 'date' => '2026-07-05',
                'image' => 'https://images.unsplash.com/photo-1621607512214-68297480165e?w=700&q=80',
                'summary' => 'Llegaron nuevas ceras y aceites para el cuidado de barba.'],
        ];

        foreach ($news as $item) {
            News::create($item);
        }

        News::factory()->count(9)->create();
    }
}
