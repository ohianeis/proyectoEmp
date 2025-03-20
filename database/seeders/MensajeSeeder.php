<?php

namespace Database\Seeders;

use App\Models\Mensaje;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MensajeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
       
        Mensaje::create([
            'mensaje'=>'El centro a modificado un título formativo'
        ]);
        Mensaje::create([
            'mensaje'=>'El centro a eliminado un título formativo'
        ]);
        Mensaje::create([
            'mensaje'=>'El centro a añadido un nuevo título formativo'
        ]);
        Mensaje::create([
            'mensaje'=>'Se ha cerrado la oferta de empleo'
        ]);
        Mensaje::create([
            'mensaje'=>'Has sido inscrito a una oferta de empleo'
        ]);
        Mensaje::create([
            'mensaje'=>'Has sido adjudicado para la oferta de empleo'
        ]);
    }
}
