<?php

namespace Database\Seeders;

use App\Models\EstadoCandidato;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadoCandidatoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $estados = [
            ['nombre' => 'Inscrito'],      // ID 1
            ['nombre' => 'Visto'],   // ID 2
            ['nombre' => 'Entrevista telefónica'],    // ID 3
            ['nombre'=>'Entrevista Presencial'], //ID4
            ['nombre'=>'Prueba técnica'], //ID 5
            ['nombre' => 'Descartado'],    // ID 6
            ['nombre' => 'Seleccionado']   // ID 7
        ];

        foreach ($estados as $estado) {
            EstadoCandidato::create($estado);
        }
    }

}
