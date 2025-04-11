<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\Empresa;
use App\Models\Oferta;
use App\Models\Titulo;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class InformeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/informes/ofertasAsignadas",
     *     summary="Obtener el total de ofertas asignadas",
     *     description="Devuelve el número total de ofertas asignadas a demandantes.",
     *     operationId="ofertasAsignadas",
     *     tags={"Informes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Número de ofertas asignadas"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function ofertasAsignadas()
    {
        try {
            // Contar las inscripciones en demandante_oferta con proceso_id = 3
            $totalAsignadas = DB::table('demandante_oferta')
                ->where('proceso_id', 3)
                ->count();
    
            return response()->json([
                'totalAsignadas' => $totalAsignadas
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
  
    
    
    /**
     * @OA\Get(
     *     path="/api/informes/ofertasCerradas",
     *     summary="Obtener el total de ofertas cerradas",
     *     description="Devuelve la cantidad y detalles de ofertas cerradas.",
     *     operationId="ofertasCerradas",
     *     tags={"Informes"},       
     *      security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Lista de ofertas cerradas"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function ofertasCerradas()
    {
        try {
            // Obtener todas las ofertas con estado 'cerrada'
            $ofertas = Oferta::where('estado_id', 2)
                ->with('empresa')
                ->get();

            return response()->json([
                'totalCerradas' => $ofertas->count(),
                'ofertas' => $ofertas
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/informes/ofertasAbiertas",
     *     summary="Obtener ofertas abiertas",
     *     description="Devuelve el número total y detalles de ofertas abiertas.",
     *     operationId="ofertasAbiertas",
     *     tags={"Informes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Lista de ofertas abiertas"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function ofertasAbiertas()
    {
        try {


            // Obtener todas las ofertas con estado 'abierta'
            $ofertas = Oferta::where('estado_id', 1)
                ->with('empresa')
                ->get();

            return response()->json([
                'totalAbiertas' => $ofertas->count(),
                'ofertas' => $ofertas
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/informes/totalDemandantes",
     *     summary="Obtener el total de demandantes registrados",
     *     description="Devuelve el número total de demandantes registrados en la plataforma.",
     *     operationId="totalDemandantes",
     *     tags={"Informes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Total de demandantes"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function totalDemandantes()
    {
        try {


            $totalDemandantes = Demandante::count();

            return response()->json([
                'totalDemandantes' => $totalDemandantes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/informes/totalEmpresas",
     *     summary="Obtener el total de empresas registradas",
     *     description="Devuelve el número total de empresas registradas y su estado.",
     *     operationId="totalEmpresas",
     *     tags={"Informes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Total de empresas y sus detalles"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function totalEmpresas()
    {
        try {


            $totalEmpresas = Empresa::count();

            return response()->json([
                'totalEmpresas' => $totalEmpresas,
                'empresas' => Empresa::select('id', 'nombre')->get()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/informes/titulosEstado",
     *     summary="Obtener el estado de los títulos",
     *     description="Devuelve cuántos títulos están activos e inactivos.",
     *     operationId="titulosEstado",
     *     tags={"Informes"},      
     * security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Total de títulos por estado"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function titulosEstado()
    {
        try {


            $titulosActivos = Titulo::where('activado', 1)->count(); // Estado activo
            $titulosInactivos = Titulo::where('activado', 2)->count(); // Estado inactivo

            return response()->json([
                'totalActivos' => $titulosActivos,
                'totalInactivos' => $titulosInactivos,
                'detalleTitulos' => Titulo::select('id', 'nombre')->get()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/informes/empresasSinOfertas",
     *     summary="Obtener empresas sin ofertas publicadas",
     *     description="Devuelve la lista de empresas que no tienen ofertas activas.",
     *     operationId="empresasSinOfertas",
     *     tags={"Informes"},
     * security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Lista de empresas sin ofertas"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function empresasSinOfertas()
    {
        try {


            $empresas = Empresa::doesntHave('ofertas')->get();

            return response()->json([
                'totalEmpresasSinOfertas' => $empresas->count(),
                'detalle' => $empresas
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/informes/ofertasSinPostulantes",
     *     summary="Obtener ofertas sin postulantes",
     *     description="Devuelve la lista de ofertas que no tienen demandantes inscritos.",
     *     operationId="ofertasSinPostulantes",
     *     tags={"Informes"},
     * security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Lista de ofertas sin postulantes"),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function ofertasSinPostulantes()
    {
        try {


            $ofertas = Oferta::doesntHave('demandantes')->get();

            return response()->json([
                'totalSinPostulantes' => $ofertas->count(),
                'detalle' => $ofertas
            ]);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
}
