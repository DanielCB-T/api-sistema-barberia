<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Barbería '.fake()->unique()->lastName(),
            'address' => fake()->streetAddress().', Oaxaca de Juárez',
            'phone' => fake()->numerify('+52 951 ### ####'),
            'opening_time' => '10:00:00',
            'closing_time' => '20:00:00',
            'image' => 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=700&q=80',
        ];
    }
}
