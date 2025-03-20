<?php

namespace Database\Seeders;

use App\Models\Centro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CentroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Centro::create([
            "alta"=>1,
            'nombre'=>'politécnico estella',
            'user_id'=>1

        ]);
    }
}
