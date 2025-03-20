<?php

namespace Database\Seeders;

use App\Models\Situacione;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SituacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Situacione::create([
            'situacion'=>'desempleado'
        ]);
        Situacione::create([
            'situacion'=>'empleado'
        ]);
        Situacione::create([
            'situacion'=>'en búsqueda activa'
        ]);
        Situacione::create([
            'situacion'=>'estudiante'
        ]);
        Situacione::create([
            'situacion'=>'freelancer'
        ]);
        
    }
}
