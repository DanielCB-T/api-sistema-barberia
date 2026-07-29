<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'summary' => fake()->paragraph(2),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'image' => 'https://images.unsplash.com/photo-1622287162716-f311baa1a2b8?w=700&q=80',
        ];
    }
}
