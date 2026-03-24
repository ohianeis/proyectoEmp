<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MotivoBajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $motivos = [
        // Visibles para Alumnos
        ['motivo' => 'He encontrado trabajo', 'visible_alumno' => true, 'visible_empresa' => false, 'solo_admin' => false],
        ['motivo' => 'No me gusta la plataforma', 'visible_alumno' => true, 'visible_empresa' => false, 'solo_admin' => false],
        
        // Visibles para Empresas
        ['motivo' => 'Ya no necesito contratar', 'visible_alumno' => false, 'visible_empresa' => true, 'solo_admin' => false],
        ['motivo' => 'Cierre de la empresa', 'visible_alumno' => false, 'visible_empresa' => true, 'solo_admin' => false],
        
        // Solo para que el Admin los use al expulsar
        ['motivo' => 'Incumplimiento de términos', 'visible_alumno' => false, 'visible_empresa' => false, 'solo_admin' => true],
        ['motivo' => 'Comportamiento inadecuado', 'visible_alumno' => false, 'visible_empresa' => false, 'solo_admin' => true],
        ['motivo' => 'Cese laboral', 'visible_alumno' => false, 'visible_empresa' => false, 'solo_admin' => true],

        
        // Genérico
        ['motivo' => 'Otros motivos', 'visible_alumno' => true, 'visible_empresa' => true, 'solo_admin' => false],
    ];

    foreach ($motivos as $motivo) {
        \App\Models\MotivoBaja::create($motivo);
    }
    }
}
