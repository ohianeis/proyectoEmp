<?php

namespace Database\Seeders;

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
        
      
     
        $this->call(EstadoSeeder::class);
        $this->call(MotivoSeeder::class);
        $this->call(MensajeSeeder::class);
        $this->call(AccioneSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(DatosPruebaSeeder::class);
        $this->call(CentroSeeder::class);
        $this->call(SituacionSeeder::class);
        $this->call(NivelesSeeder::class);
        $this->call(PorcesosSeeder::class);//poner bien nombre seeder!!!
    //creo empresa pruebas aqui por fallo en ordenes creacion migraciones y seeders
    
    Empresa::create([
        'alta'=>1,
        'cif'=>'a0000000',
        'nombre'=>'empresa1',
        'localidad'=>'pamplona',
        'user_id'=>2,
        'centro_id'=>1
    ]);
    Empresa::create([
        'alta'=>0,
        'cif'=>'b1111111',
        'nombre'=>'empresa2',
        'localidad'=>'pamplona',
        'user_id'=>4,
        'centro_id'=>1
    ]);
    Oferta::create([
        'nombre'=>'prueba',
        'observacion'=>'prueba',
        'tipoContrato'=>'prueba',
        'horario'=>'prueba',
        'nPuestos'=>1,
        'estado_id'=>1,
        'empresa_id'=>1
  
    ]);
    Oferta::create([
        'nombre'=>'prueba2',
        'observacion'=>'prueba2',
        'tipoContrato'=>'prueba2',
        'horario'=>'prueba2',
        'nPuestos'=>1,
        'estado_id'=>1,
        'empresa_id'=>1
  
    ]);
    Oferta::create([
        'nombre'=>'prueba cierre oferta',
        'observacion'=>'prueba, ver los estados cierre',
        'tipoContrato'=>'prueba',
        'horario'=>'prueba',
        'fechaCierre'=>'2025-03-13',
        'nPuestos'=>1,
        'motivo_id'=>1,
        'estado_id'=>1,
        'empresa_id'=>2
  
    ]);
    //creo demandantes de ejemplos
    Demandante::create([
        'nombre'=>'Demandante1',
        'telefono'=>111111111,
        'experienciaLaboral'=>'Cuatro años como desarrollador php',
        'situacione_id'=>1,
        'centro_id'=>1,
        'user_id'=>3
    ]);
    Demandante::create([
        'nombre'=>'Demandante2',
        'telefono'=>222222222,
        'experienciaLaboral'=>'fronted developed',
        'situacione_id'=>3,
        'centro_id'=>1,
        'user_id'=>3
    ]);
    //creo algun titulo de prueba en la tabla
    Titulo::create([
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
    ]);
    //añado titulos que poseen demandates demandante_titulo
    
    }
}
