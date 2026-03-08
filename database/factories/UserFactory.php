<?php

namespace Database\Factories;

use App\Enums\UserEstado;
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
        // 1. Generamos primero el nombre
        $nombre = $this->faker->firstName();
        $apellido = $this->faker->lastName();
        $nombreCompleto = $nombre . ' ' . $apellido;

        // 2. Creamos un email basado en ese nombre
        // Str::slug convierte "Ana Ruiz" en "ana-ruiz"
        // Str::replace cambia el "-" por "." para que parezca un email real: "ana.ruiz"
        $emailBase = Str::replace('-', '.', Str::slug($nombreCompleto));
    $email = $emailBase . $this->faker->unique()->numberBetween(1, 99) . '@ejemplo.com';

  return [
        'name' => $nombreCompleto,
        // Usamos una función anónima para el email. 
        // Así, si 'name' cambia en un estado, el email se generará con el nuevo nombre.
        'email' => function (array $attributes) {
            $slug = Str::slug($attributes['name'], '.');
            return $slug . fake()->unique()->numberBetween(1, 99) . '@ejemplo.com';
        },
        'password' => bcrypt('prueba'),
        'validado' => $this->faker->boolean(80),
        'status' => UserEstado::PENDIENTE_VALIDACION->value,
        'role_id' => 3, 
    ];

}

     // Estado para usuarios ya validados
     
    public function activo(): static
    {
        return $this->state(fn(array $attributes) => [
            'validado' => true,
            'status' => UserEstado::ACTIVO->value,
        ]);
    }
    /**
 * Estado para usuarios que son empresas.
 */
public function empresa(): static
{
    return $this->state(fn (array $attributes) => [
        'role_id' => 2, 
    ]);
}
public function conNombreEmpresa(): static
{
    return $this->state(fn (array $attributes) => [
        'name' => $this->faker->company() // Cambia "Ana Ruiz" por "Tech Solutions S.L."
    ]);
}

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

}
