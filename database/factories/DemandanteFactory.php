<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Demandante>
 */
class DemandanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       return [
        'nombre' => $this->faker->name(),
        'telefono' => $this->faker->numberBetween(600000000, 799999999), // Teléfonos españoles
        'experienciaLaboral' => $this->faker->paragraphs(2, true), // Un texto largo con su experiencia
        'situacione_id' => $this->faker->numberBetween(1, 5), // Selecciona una de las 5 situaciones
        'centro_id' => 1, //  centro 
    ];
    }
}
