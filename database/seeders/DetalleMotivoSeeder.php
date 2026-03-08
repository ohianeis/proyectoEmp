<?php

namespace Database\Seeders;

use App\Models\DetalleMotivo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DetalleMotivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $detalles = [
        ['nombre' => 'Candidato seleccionado de la bolsa', 'motivo_id' => 1, 'activo' => true],
        ['nombre' => 'Puesto cancelado por la empresa', 'motivo_id' => 2, 'activo' => true],
        ['nombre' => 'Cubierta por medios externos', 'motivo_id' => 2, 'activo' => true],
        ['nombre' => 'Candidatos no cumplen perfil', 'motivo_id' => 2, 'activo' => true],
        ['nombre' => 'Falta de presupuesto', 'motivo_id' => 2, 'activo' => true],
        ['nombre' => 'Rechazo de oferta económica', 'motivo_id' => 2, 'activo' => true]
    ];

    foreach ($detalles as $detalle) {
        DetalleMotivo::create($detalle);
    }
    }
}
