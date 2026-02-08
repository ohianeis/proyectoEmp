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
        ['id' => 1, 'nombre' => 'Inscrito'],
        ['id' => 2, 'nombre' => 'Visto'],
        ['id' => 3, 'nombre' => 'Entrevista telefónica'],
        ['id' => 4, 'nombre' => 'Entrevista Presencial'],
        ['id' => 5, 'nombre' => 'Prueba técnica'],
        ['id' => 6, 'nombre' => 'Descartado'],
        ['id' => 7, 'nombre' => 'Seleccionado'],
        ['id' => 8, 'nombre' => 'Retirada por el candidato'] 
    ];

        foreach ($estados as $estado) {
            EstadoCandidato::create($estado);
        }
    }

}
