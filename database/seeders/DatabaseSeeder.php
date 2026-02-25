<?php

namespace Database\Seeders;
use App\Enums\UserEstado;
use App\Models\Demandante;
use App\Models\Empresa;
use App\Models\Notificacione;
use App\Models\Oferta;
use App\Models\Titulo;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\Notification;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();



        $this->call(EstadoSeeder::class); //estado de una oferta
        $this->call(MotivoSeeder::class);
        $this->call(MensajeSeeder::class);
        $this->call(AccioneSeeder::class);
        $this->call(RolesSeeder::class);
        //  $this->call(DatosPruebaSeeder::class);
        //crear admin ya para que no de fallo centroSeeder
        User::factory()->create([
            'name' => 'Administrador CIP Burlada',
            'email' => 'admin@example.com',
            'password' => bcrypt('administrador'), // uso admin para diferenciar del resto
            'role_id' => 1,
            'validado' => 1,
            'status' => \App\Enums\UserEstado::ACTIVO,
        ]);
        $this->call(CentroSeeder::class);
        $this->call(SituacionSeeder::class);
        $this->call(NivelesSeeder::class);
        $this->call(PorcesosSeeder::class); //poner bien nombre seeder!!!
        $this->call(EstadoCandidatoSeeder::class); //estado de un candidado en un proceso oferta
        $this->call(MotivoBajaSeeder::class);//motivos de baja seeder creado
        //creacion para ejemplo de familias titulos
        $familiasData = [
            ['nombre' => 'Informática y Comunicaciones'],
            ['nombre' => 'Administración y Gestión'],
            ['nombre' => 'Sanidad'],
            ['nombre' => 'Hostelería y Turismo'],
            ['nombre' => 'Comercio y Marketing']
        ];

        foreach ($familiasData as $f) {
            \App\Models\Familia::create($f);
        }
        //seeder de titulos
        $superior = \App\Models\Nivele::where('nivel', 'Grado Superior')->first()->id;
        $medio = \App\Models\Nivele::where('nivel', 'Grado Medio')->first()->id;
        $basico = \App\Models\Nivele::where('nivel', 'Grado Básico')->first()->id;
        // Obtenemos los IDs de las familias para asignar
        $fInformática = \App\Models\Familia::where('nombre', 'Informática y Comunicaciones')->first()->id;
        $fAdmin = \App\Models\Familia::where('nombre', 'Administración y Gestión')->first()->id;
        $titulos = [
            ['nombre' => 'Desarrollo de Aplicaciones Web', 'nivel' => $superior, 'familia' => $fInformática],
            ['nombre' => 'Desarrollo de Aplicaciones Multiplataforma', 'nivel' => $superior, 'familia' => $fInformática],
            ['nombre' => 'Sistemas Microinformáticos y Redes', 'nivel' => $medio, 'familia' => $fInformática],
            ['nombre' => 'Administración y Finanzas', 'nivel' => $superior, 'familia' => $fAdmin],
        ];

        foreach ($titulos as $t) {
            \App\Models\Titulo::create([
                'nombre' => $t['nombre'],
                'activado' => true,
                'nivele_id' => $t['nivel'],
                'familia_id' => $t['familia'],
                'centro_id' => 1,
            ]);
        }



        //  Crear empresas validadas y sus ofertas
        User::factory(5)->create(['role_id' => 2, 'validado' => 1,'status' => \App\Enums\UserEstado::ACTIVO])->each(function ($user) {
            // A cada usuario le creamos su perfil de empresa
            $empresa = $user->empresa()->create(
                \App\Models\Empresa::factory()->make()->toArray()
            );

            // Cada empresa crea 2 ofertas
            $ofertas = \App\Models\Oferta::factory(2)->create([
                'empresa_id' => $empresa->id
            ]);

            // A cada oferta le asignamos 1 o 2 títulos aleatorios (relación muchos a muchos)
            $ofertas->each(function ($oferta) {
                $titulosAleatorios = \App\Models\Titulo::inRandomOrder()->take(rand(1, 2))->pluck('id');
                $oferta->titulos()->attach($titulosAleatorios);
            });
        });
        //crear con factory para demo 5 empresas no validadas
        User::factory(5)->create([
            'role_id' => 2,
            'validado' => 0,
        ]);
        //crear factory con 5 alumos validados
        User::factory(5)->create([
            'role_id' => 3,
            'validado' => 1,
            'status' => \App\Enums\UserEstado::ACTIVO
        ])->each(function ($user) {
            // Creamos el perfil de demandante
            $demandante = $user->demandante()->create(
                \App\Models\Demandante::factory()->make([
                    'nombre' => $user->name // Usamos el nombre del usuario para que coincidan
                ])->toArray()
            );

            // Creamos la dirección polimórfica para el demandante
            $demandante->direccion()->create(
                \App\Models\Direccione::factory()->make()->toArray()
            );
        });
        //crear factory con 5 alumnos no validados
        User::factory(5)->create([
            'role_id' => 3,
            'validado' => 0, // Las empresas suelen estar validadas en la demo
        ]);



        //creo algun titulo de prueba en la tabla
        /*Titulo::create([
        'nombre'=>'Fontanería',
        'activado'=>1,
        'nivele_id'=>1,
        'centro_id'=>1
    ]);
    Titulo::create([
        'nombre'=>'técnico administrativo',
        'activado'=>1,
        'nivele_id'=>2,
        'centro_id'=>1
    ]);
    Titulo::create([
        'nombre'=>'desarrollo de aplicaciones web',
        'activado'=>1,
        'nivele_id'=>3,
        'centro_id'=>1
    ]);*/
    }
}
