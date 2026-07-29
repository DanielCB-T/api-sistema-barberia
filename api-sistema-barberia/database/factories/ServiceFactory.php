<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['Corte', 'Barba', 'Limpieza', 'Degradado', 'Color'];

        return [
            'name' => fake()->randomElement([
                'Corte infantil', 'Corte ejecutivo', 'Diseño de barba', 'Tinte de barba',
                'Depilación de cejas', 'Mascarilla facial', 'Corte + lavado', 'Afeitado clásico',
            ]),
            'category' => fake()->randomElement($categories),
            'price' => fake()->numberBetween(70, 260),
            'duration' => fake()->randomElement([20, 30, 40, 45, 60]),
            'description' => fake()->sentence(12),
            'image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600&q=80',
        ];
    }
}
