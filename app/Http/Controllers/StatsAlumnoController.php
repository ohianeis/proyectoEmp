<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(name="Dashboard Alumno", description="Estadísticas y métricas para alumnos")
 */
class StatsAlumnoController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/alumno/stats",
     * summary="Obtener estadísticas del alumno",
     * tags={"Dashboard Alumno"},
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Estadísticas recuperadas",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean"),
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="No encontrado"),
     * @OA\Response(response=500, description="Error servidor")
     * )
     */
    public function getDashboardStats()
    {
        try {
            $user = Auth::user();
            $demandante = $user->demandante;

            if (!$demandante) {
                return response()->json(['message' => 'Perfil no encontrado'], 404);
            }

            $demandanteId = $demandante->id;

            // 1.  CRITERIOS DE AFINIDAD (Títulos y Familias del alumno)
            $misTitulosIds = $demandante->titulos->pluck('id')->toArray();
            $misFamiliasIds = $demandante->titulos->pluck('familia_id')->unique()->toArray();

            // 2. CONTEO DE TÍTULOS EN PERFIL
            $totalTitulos = $demandante->titulos()->count();

            // 3. INSCRIPCIONES ACTIVAS (Donde el alumno ya participa)
            // Filtramos: Que la oferta no esté cerrada (2) 
            // y que el alumno no esté Finalizado (6), Adjudicado (7 en estado / 3 en proceso) o Retirado (8)
            $inscripcionesActivas = $demandante->ofertas()
                ->where('ofertas.estado_id', '!=', 2)
                ->wherePivotNotIn('estado_candidato_id', [6, 7, 8])
                ->wherePivot('proceso_id', '!=', 3) // 3 = Adjudicada/Conseguida
                ->count();

            // 4. NUEVAS OPORTUNIDADES (Ofertas disponibles según su perfil)
            $nuevasOfertas = Oferta::where('estado_id', 1) // Solo ofertas abiertas
                ->whereHas('empresa.user', function ($q) {
                    // Solo empresas activas y validadas por administración
                    $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                        ->where('validado', true);
                })
                // EXCLUIR aquellas en las que el alumno ya está inscrito
                ->whereDoesntHave('demandantes', function ($q) use ($demandanteId) {
                    $q->where('demandante_id', $demandanteId);
                })

                ->where(function ($query) use ($misTitulosIds, $misFamiliasIds) {
                    $query->whereHas('titulos', function ($q) use ($misTitulosIds) {
                        // Caso A: La oferta pide títulos específicos y yo tengo alguno
                        $q->whereIn('titulos.id', $misTitulosIds);
                    })
                        ->orWhere(function ($q) use ($misFamiliasIds) {
                            // Caso B: La oferta NO pide títulos, pero es de mi Familia Profesional
                            $q->whereIn('familia_id', $misFamiliasIds)
                                ->whereDoesntHave('titulos');
                        });
                })
                ->count();

            // 5. DATOS PARA EL GRÁFICO (Grupos excluyentes para que los números cuadren)
            $statsGrafico = [
                // "En proceso": Inscrito pero sin resolución final (ni éxito ni fracaso)
                'proceso'     => (int) $demandante->ofertas()
                    ->wherePivotIn('estado_candidato_id', [1, 3, 4, 5])
                    ->wherePivot('proceso_id', '!=', 3)
                    ->count(),

                // "Conseguido": Ofertas donde el proceso_id es 3 (Adjudicada)
                'conseguido'  => (int) $demandante->ofertas()
                    ->wherePivot('proceso_id', 3)
                    ->count(),

                // "Finalizados": Candidaturas descartadas por la empresa (estado 6)
                'finalizados' => (int) $demandante->ofertas()
                    ->wherePivot('estado_candidato_id', 6)
                    ->count(),

                // "Retiradas": Candidaturas abandonadas por el alumno (estado 8)
                'retiradas'   => (int) $demandante->ofertas()
                    ->wherePivot('estado_candidato_id', 8)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas del dashboard obtenidas correctamente',
                'data' => [
                    'cards' => [
                        'inscripciones' => $inscripcionesActivas,
                        'titulos'       => $totalTitulos,
                        'ofertas'       => $nuevasOfertas, // Este número ahora es real según su perfil
                    ],
                    'grafico' => $statsGrafico
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar estadísticas',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }
}
