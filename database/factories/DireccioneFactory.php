<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class DireccioneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'linea1' => $this->faker->streetAddress(),
            'ciudad' => $this->faker->city(),
            'provincia' => $this->faker->state(),
            'codigoPostal' => $this->faker->numberBetween(10000, 52999),
            'visible' => $this->faker->boolean(80),
        ];
    }
}
