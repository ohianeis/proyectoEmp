<?php

namespace Database\Seeders;

use App\Models\Accione;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccioneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Accione::create([
            'tipo'=>'Modificación título',
            'mensaje_id'=>1
      
        ]);
        Accione::create([
            'tipo'=>'Eliminación título',
            'mensaje_id'=>2
        ]);
        Accione::create([
            'tipo'=>'Creación título',
            'mensaje_id'=>3
        ]);
        Accione::create([
            'tipo'=>'Oferta cerrada',
            'mensaje_id'=>4
        ]);
        Accione::create([
            'tipo'=>'demandate añadido',
            'mensaje_id'=>5
        ]);
        Accione::create([
            'tipo'=>'Oferta adjudicada',
            'mensaje_id'=>6
        ]);
    }
}
