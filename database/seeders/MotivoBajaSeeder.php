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
        ['motivo' => 'He encontrado trabajo', 'visibleAlumno' => true, 'visibleEmpresa' => false, 'soloAdmin' => false],
        ['motivo' => 'No me gusta la plataforma', 'visibleAlumno' => true, 'visibleEmpresa' => false, 'soloAdmin' => false],
        
        // Visibles para Empresas
        ['motivo' => 'Ya no necesito contratar', 'visibleAlumno' => false, 'visibleEmpresa' => true, 'soloAdmin' => false],
        ['motivo' => 'Cierre de la empresa', 'visibleAlumno' => false, 'visibleEmpresa' => true, 'soloAdmin' => false],
        
        // Solo para que el Admin los use al expulsar
        ['motivo' => 'Incumplimiento de términos', 'visibleAlumno' => false, 'visibleEmpresa' => false, 'soloAdmin' => true],
        ['motivo' => 'Comportamiento inadecuado', 'visibleAlumno' => false, 'visibleEmpresa' => false, 'soloAdmin' => true],
        
        // Genérico
        ['motivo' => 'Otros motivos', 'visibleAlumno' => true, 'visibleEmpresa' => true, 'soloAdmin' => false],
    ];

    foreach ($motivos as $motivo) {
        \App\Models\MotivoBaja::create($motivo);
    }
    }
}
