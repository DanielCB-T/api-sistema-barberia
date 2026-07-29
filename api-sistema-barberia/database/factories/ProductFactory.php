<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Pomada texturizadora', 'Bálsamo para barba', 'Shampoo carbón activado',
                'Loción after shave', 'Peine de madera', 'Tijeras de precisión',
                'Toalla caliente reutilizable', 'Kit de afeitado',
            ]),
            'price' => fake()->numberBetween(90, 450),
            'stock' => fake()->numberBetween(5, 60),
            'description' => fake()->sentence(10),
            'image' => 'https://images.unsplash.com/photo-1621607512214-68297480165e?w=600&q=80',
        ];
    }
}
