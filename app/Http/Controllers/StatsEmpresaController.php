<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class StatsEmpresaController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/empresa/stats",
     * summary="Obtener estadísticas del dashboard para la empresa",
     * tags={"Dashboard Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Estadísticas obtenidas correctamente",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="ofertas_activas", type="integer", example=5),
     * @OA\Property(property="candidatos_nuevos", type="integer", example=12),
     * @OA\Property(property="total_cerradas", type="integer", example=20)
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="No autenticado"
     * ),
     * @OA\Response(
     * response=403,
     * description="No tiene permisos de empresa"
     * )
     * )
     */
    public function getStatsEmpresa()
    {
        try {
            $user = Auth::user();
            $empresaId = $user->empresa->id;

            // 1. Ofertas que están actualmente publicadas
            $ofertasActivas = Oferta::where('empresa_id', $empresaId)
                ->where('estado_id', 1)
                ->count();

            // 2. Total histórico de ofertas cerradas (todas)
            $totalCerradas = Oferta::where('empresa_id', $empresaId)
                ->where('estado_id', 2)
                ->count();

            // 3. Ofertas cerradas donde se seleccionó a alguien (Éxito)
            $cerradasConExito = Oferta::where('empresa_id', $empresaId)
                ->where('estado_id', 2)
                ->where('motivo_id', 1) // <--- Cambiamos el whereHas por este simple where
                ->count();

            // 4. Candidatos en ofertas activas que no han sido revisados
            $candidatosNuevos = DB::table('demandante_oferta')
                ->join('ofertas', 'demandante_oferta.oferta_id', '=', 'ofertas.id')
                ->where('ofertas.empresa_id', $empresaId)
                ->where('ofertas.estado_id', 1)
                ->where('demandante_oferta.revisado', false)
                ->count();

            // 5. Listado para la expansión de la tarjeta
            $ofertasConPendientes = Oferta::where('empresa_id', $empresaId)
                ->where('estado_id', 1)
                ->whereHas('demandantes', function ($query) {
                    $query->where('demandante_oferta.revisado', false);
                })
                ->withCount(['demandantes as nuevos' => function ($query) {
                    $query->where('demandante_oferta.revisado', false);
                }])
                ->get(['id', 'nombre']);

            return response()->json([
                'data' => [
                    'ofertas_activas'    => $ofertasActivas,
                    'total_cerradas'     => $totalCerradas,
                    'cerradas_con_exito' => $cerradasConExito,
                    'candidatos_nuevos'  => $candidatosNuevos,
                    'ofertas_con_pendientes' => $ofertasConPendientes
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json(['mensaje' => $e->getMessage()], 500);
        }
    }
}
