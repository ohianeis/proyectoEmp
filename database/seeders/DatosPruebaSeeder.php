<?php

namespace Database\Seeders;

use App\Models\Centro;
use App\Models\Empresa;
use App\Models\Notificacione;
use App\Models\Oferta;
use App\Models\Titulo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
             //ofertas para ejemplos
       
   
    Notificacione::create([
        'accione_id'=>'1',
        'notificacioneable_id'=>1,
        'notificacioneable_type'=>Empresa::class,
        'relacioneable_id'=>1,
        'relacioneable_type'=>Oferta::class
       
    
    ]);
    Notificacione::create([
        'accione_id'=>'4',
        'notificacioneable_id'=>1,
        'notificacioneable_type'=>Centro::class,
        'relacioneable_id'=>1,
        'relacioneable_type'=>Titulo::class
       
    ]);
    User::factory()->create([
        'name' => 'administrador',
        'email' => 'admin@example.com',
        'password'=>Hash::make('1234admin'),
        'role_id'=>1
    ]);
    User::factory()->create([
        'name' => 'empresa',
        'email' => 'empresa@example.com',
        'password'=>Hash::make('1234empresa'),
        'role_id'=>2
    ]);
    User::factory()->create([
        'name' => 'demandante',
        'email' => 'demandante@example.com',
        'password'=>Hash::make('1234demandante'),
        'role_id'=>3
    ]);
    User::factory()->create([
        'name' => 'empresa2',
        'email' => 'empresa2@example.com',
        'password'=>Hash::make('1234empresa2'),
        'role_id'=>2
    ]);
    User::factory()->create([
        'name' => 'demandante2',
        'email' => 'demandante2@example.com',
        'password'=>Hash::make('1234demandante2'),
        'role_id'=>3
    ]);



    }
}
