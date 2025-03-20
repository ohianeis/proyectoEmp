<?php

namespace Database\Seeders;

use App\Models\Motivo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MotivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Motivo::create([
            'tipo'=>'asignada'
        ]);
        Motivo::create([
            'tipo'=>'sin demandante de la bolsa'
        ]);
    }
}
