<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Oferta>
 */
class OfertaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'nombre' => $this->faker->jobTitle(),
        'observacion' => $this->faker->paragraph(),
        'tipoContrato' => $this->faker->randomElement(['Indefinido', 'Temporal', 'Prácticas']),
        'horario' => $this->faker->randomElement(['Mañana', 'Tarde', 'Jornada Completa']),
        'fechaCierre' => null,
        'nPuestos' => $this->faker->numberBetween(1, 3),
        'estado_id' => 1, // Por defecto "Abierta"
        'motivo_id' => null, // Normalmente nulo si está abierta
        'incorporacion'=>$this->faker->optional(0.8)->dateTimeBetween('+1 week', '+1 month'),
        'esAnonima' => $this->faker->boolean(20), // 20% de probabilidad de ser anónima para probar ambos casos
    ];
    }
}
