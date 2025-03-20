<?php

namespace Database\Seeders;

use App\Models\Proceso;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PorcesosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Proceso::create([
            'estado'=>'en proceso'
        ]);
        Proceso::create([
            'estado'=>'cerrada'
        ]);
        Proceso::create([
            'estado'=>'adjudicada'
        ]);
    }
}
