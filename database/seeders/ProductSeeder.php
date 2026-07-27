<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Cera moldeadora', 'price' => 180, 'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1621607512214-68297480165e?w=600&q=80',
                'description' => 'Fijación media, acabado mate.'],
            ['name' => 'Aceite para barba', 'price' => 150, 'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1621607512022-6aa69f8d3a72?w=600&q=80',
                'description' => 'Hidrata y suaviza, aroma amaderado.'],
            ['name' => 'Shampoo anticaída', 'price' => 210, 'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1585232351009-aa87416fca90?w=600&q=80',
                'description' => 'Fórmula fortalecedora de uso diario.'],
            ['name' => 'Navaja de afeitar clásica', 'price' => 350, 'stock' => 10,
                'image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600&q=80',
                'description' => 'Acero inoxidable, mango de madera.'],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        Product::factory()->count(8)->create();
    }
}
