<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->numerify('+52 951 ### ####'),
            'birthdate' => fake()->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d'),
            'role' => 'client',
            'branch_id' => null,
            'avatar' => 'https://i.pravatar.cc/150?u='.fake()->uuid(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'branch_id' => null,
        ]);
    }

    public function barber(?int $branchId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'barber',
            'branch_id' => $branchId,
        ]);
    }
}
