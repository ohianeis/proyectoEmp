<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

class StatsAlumnoController extends Controller
{
    public function getDashboardStats()
    {
        try {

            $user = Auth::user();

            // 1. OBTENEMOS EL OBJETO MODELO (no solo el ID)
            $demandante = $user->demandante;

            if (!$demandante) {
                return response()->json(['message' => 'Perfil no encontrado'], 404);
            }

            // 2. CONTEO DE TÍTULOS
            // No hace falta poner ->where('demandante_id', ...), 
            // Laravel ya lo sabe porque llamas a la relación desde el objeto $demandante.
            $totalTitulos = $demandante->titulos()->count();

            // 3. INSCRIPCIONES ACTIVAS
            $inscripcionesActivas = $demandante->ofertas()
                ->where('ofertas.estado_id', '!=', 2)
                ->wherePivotNotIn('estado_candidato_id', [
                    6, // Excluimos descartados (finalizados)
                    7, // Excluimos seleccionados (ya no es "activa", ya es éxito)
                    8  // Excluimos retirados
                ])->count();

            // 4. NUEVAS OPORTUNIDADES
            // Aquí SÍ necesitamos el ID numérico para comparar en la consulta de exclusión
            $demandanteId = $demandante->id;
            $nuevasOfertas = Oferta::where('estado_id', 1)
                ->whereDoesntHave('demandantes', function ($q) use ($demandanteId) {
                    $q->where('demandante_id', $demandanteId);
                })
                ->count();

            // 5. DATOS GRÁFICO
            $statsGrafico = [
                'proceso'     => (int) $demandante->ofertas()->wherePivotIn('estado_candidato_id', [1, 3, 4, 5])->count(),
                'conseguido'  => (int) $demandante->ofertas()->wherePivot('estado_candidato_id', 7)->count(),
                'finalizados' => (int) $demandante->ofertas()->wherePivot('estado_candidato_id', 6)->count(),
                'retiradas'   => (int) $demandante->ofertas()->wherePivot('estado_candidato_id', 8)->count(),
            ];

            return response()->json([
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => [
                    'cards' => [
                        'inscripciones' => $inscripcionesActivas,
                        'titulos'       => $totalTitulos,
                        'ofertas'       => $nuevasOfertas,
                    ],
                    'grafico' => $statsGrafico
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas del dashboard',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
