<?php

namespace Database\Seeders;

use App\Enums\UserEstado;
use App\Models\Demandante;
use App\Models\DetalleMotivo;
use App\Models\Direccione;
use App\Models\Empresa;
use App\Models\Oferta;
use App\Models\Titulo;
use App\Models\User;
use App\Models\Familia;
use App\Models\Nivele;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seeders de configuración base
        $this->call([
            RolesSeeder::class,
            EstadoSeeder::class,
            MotivoSeeder::class,
            DetalleMotivoSeeder::class,
            MensajeSeeder::class,
            AccioneSeeder::class,
            SituacionSeeder::class,
            NivelesSeeder::class,
            PorcesosSeeder::class,
            EstadoCandidatoSeeder::class,
            MotivoBajaSeeder::class,
        ]);

        // 2. Crear Administrador (ID 1 para CentroSeeder)
      User::create([
    'id' => 1,
    'name' => env('ADMIN_NAME', 'Administrador Sistema'), 
    'email' => env('ADMIN_EMAIL', 'admin@centro.com'),
    'password' => bcrypt(env('ADMIN_PASSWORD', 'secret1234')), // Password desde .env
    'role_id' => 1,
    'validado' => 1,
    'status' => UserEstado::ACTIVO,
    'change_pass' => 0, // El superadmin no resetea su pass
]);

        $this->call([CentroSeeder::class]);

        // 3. Crear Familias y Títulos
        $familiasData = [
            ['nombre' => 'Informática y Comunicaciones'],
            ['nombre' => 'Administración y Gestión'],
            ['nombre' => 'Sanidad'],
            ['nombre' => 'Hostelería y Turismo'],
            ['nombre' => 'Comercio y Marketing']
        ];
        foreach ($familiasData as $f) Familia::create($f);

        $superior = Nivele::where('nivel', 'Grado Superior')->first()->id;
        $medio = Nivele::where('nivel', 'Grado Medio')->first()->id;
        $fInf = Familia::where('nombre', 'Informática y Comunicaciones')->first()->id;
        $fAdm = Familia::where('nombre', 'Administración y Gestión')->first()->id;

        $titulosSeed = [
            ['nombre' => 'Desarrollo de Aplicaciones Web', 'nivele_id' => $superior, 'familia_id' => $fInf],
            ['nombre' => 'Desarrollo de Aplicaciones Multiplataforma', 'nivele_id' => $superior, 'familia_id' => $fInf],
            ['nombre' => 'Sistemas Microinformáticos y Redes', 'nivele_id' => $medio, 'familia_id' => $fInf],
            ['nombre' => 'Administración y Finanzas', 'nivele_id' => $superior, 'familia_id' => $fAdm],
        ];

        foreach ($titulosSeed as $t) {
            Titulo::create(array_merge($t, ['activado' => true, 'centro_id' => 1]));
        }

        $todosLosTitulosIds = Titulo::pluck('id')->toArray();

        // 4. EMPRESAS (10 validadas con 11 ofertas cada una + 5 sin validar)
        User::factory(10)
            ->empresa()
            ->conNombreEmpresa()
            ->create(['validado' => 1, 'status' => UserEstado::ACTIVO])
            ->each(function ($user) use ($todosLosTitulosIds) {
                $empresa = $user->empresa()->create(['nombre' => $user->name]);

                for ($i = 1; $i <= 11; $i++) {
                    // Oferta Abierta
                    $o = Oferta::factory()->create(['empresa_id' => $empresa->id, 'estado_id' => 1, 'nombre' => "Oferta Abierta #$i"]);
                    $o->titulos()->attach(fake()->randomElements($todosLosTitulosIds, rand(1, 2)));

                    // Oferta Cerrada
                    $oc = Oferta::factory()->create([
                        'empresa_id' => $empresa->id,
                        'estado_id' => 2,
                        'nombre' => "Oferta Cerrada #$i",
                        'fechaCierre' => now(),
                        'motivo_id' => 2,
                        'detalle_motivo_id' => DetalleMotivo::where('motivo_id', 2)->first()->id
                    ]);
                    $oc->titulos()->attach(fake()->randomElements($todosLosTitulosIds, rand(1, 2)));
                }
            });

        User::factory(5)->create(['role_id' => 2, 'validado' => 0, 'status' => UserEstado::PENDIENTE_VALIDACION]);

        // 5. ALUMNOS VALIDADOS (30) - Solución a campos obligatorios en pivote
        User::factory(30)->create([
            'role_id' => 3,
            'validado' => 1,
            'status' => UserEstado::ACTIVO
        ])->each(function ($user) use ($todosLosTitulosIds) {
            $demandante = $user->demandante()->create(
                Demandante::factory()->make(['nombre' => $user->name])->toArray()
            );

            $demandante->direccion()->create(Direccione::factory()->make()->toArray());

            $titulosParaAsignar = fake()->randomElements($todosLosTitulosIds, rand(1, 2));

            foreach ($titulosParaAsignar as $id) {
                // Aquí pasamos todos los campos que SQL nos ha ido reclamando
                $demandante->titulos()->attach($id, [
                    'centro' => 'CIP Burlada',
                    'año' => rand(2020, 2025),
                    'cursando' => rand(0, 1)
                ]);
            }
        });

        // 6. ALUMNOS NO VALIDADOS (5)
        User::factory(5)->create([
            'role_id' => 3,
            'validado' => 0,
            'status' => UserEstado::PENDIENTE_VALIDACION
        ])->each(function ($user) {
            $d = $user->demandante()->create(Demandante::factory()->make(['nombre' => $user->name])->toArray());
            $d->direccion()->create(Direccione::factory()->make()->toArray());
        });
    }
}
