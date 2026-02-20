<?php

namespace Database\Seeders;

use App\Models\Nivele;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NivelesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Nivele::create([
            'nivel'=>'Grado Básico'
        ]);
        Nivele::create([
            'nivel'=>'Grado Medio'
        ]);
        Nivele::create([
            'nivel'=>'Grado Superior'
        ]);
    }
}
