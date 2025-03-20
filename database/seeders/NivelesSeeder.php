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
            'nivel'=>'grado básico'
        ]);
        Nivele::create([
            'nivel'=>'grado medio'
        ]);
        Nivele::create([
            'nivel'=>'grado superior'
        ]);
    }
}
