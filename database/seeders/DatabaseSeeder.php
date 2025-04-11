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

    
    }
}
