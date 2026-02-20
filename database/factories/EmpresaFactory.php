<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Empresa>
 */
class EmpresaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Array de municipios de Navarra para mayor realismo
        $pueblosNavarra = [
            'Burlada',
            'Pamplona',
            'Villava',
            'Huarte',
            'Ansoáin',
            'Zizur Mayor',
            'Barañáin',
            'Egüés',
            'Tafalla',
            'Estella',
            'Tudela',
            'Olite',
            'Sangüesa',
            'Alsasua',
            'Corella'
        ];
        return [
            'cif' => $this->faker->bothify('?########'), // Genera algo como A12345678
            'nombre' => $this->faker->catchPhrase() . ' ' . $this->faker->companySuffix(),
            'descripcion' => $this->faker->paragraph(),
            'localidad' => $this->faker->randomElement($pueblosNavarra),
            'web' => $this->faker->url(),
            'telefono_contacto' => $this->faker->phoneNumber(),
            'centro_id' => 1,
        ];
    }
}
