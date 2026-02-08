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
                'data' => $totalAsignadas, // Envolvemos el resultado en data
                'message' => 'Total de ofertas asignadas recuperado'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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
                'data' => [
                    'total' => $ofertas->count(),
                    'listado' => $ofertas
                ],
                'message' => 'Informe de ofertas cerradas generado correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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
                'data' => [
                    'total' => $ofertas->count(),
                    'listado' => $ofertas
                ],
                'message' => 'Informe de ofertas abiertas generado con éxito'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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
                'data' => $totalDemandantes, // Mandamos el número directamente en data
                'message' => 'Conteo de demandantes recuperado'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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
            $empresas = Empresa::select('id', 'nombre')->get();
          return response()->json([
            'data' => [
                'total' => $totalEmpresas,
                'listado' => $empresas
            ],
            'message' => 'Estadísticas de empresas recuperadas correctamente'
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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
            $titulosInactivos = Titulo::where('activado', 0)->count(); // Estado inactivo

          return response()->json([
            'data' => [
                'totalActivos' => $titulosActivos,
                'totalInactivos' => $titulosInactivos,
                'listado' => Titulo::select('id', 'nombre', 'activado')->get()
            ],
            'message' => 'Estado del catálogo académico recuperado'
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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


          $empresas = Empresa::doesntHave('ofertas')
            ->with('user:id,email') 
            ->get();

          return response()->json([
            'data' => [
                'total' => $empresas->count(),
                'listado' => $empresas
            ],
            'message' => 'Informe de empresas sin actividad generado correctamente'
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
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


          $ofertas = Oferta::doesntHave('demandantes')
        ->with('empresa:id,nombre') // Solo lo mínimo
        ->select('id', 'nombre', 'empresa_id', 'created_at') // Filtramos columnas pesadas
        ->get();

           return response()->json([
            'data' => [
                'total' => $ofertas->count(),
                'listado' => $ofertas
            ],
            'message' => 'Informe de ofertas sin candidatos generado correctamente'
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
 * Obtener detalle de una empresa específica para el Admin
 */
public function detalleEmpresaAdmin($id)
{
    try {
        // Buscamos la empresa por ID con sus relaciones
        $empresa = Empresa::with(['direccion', 'user'])->find($id);

        if (!$empresa) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $empresa
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el detalle de la empresa',
            'errors'  => $e->getMessage()
        ], 500);
    }
}
/**para consultar detalle oferta */
public function detalleOfertaAdmin($id)
{
    try {
        // Buscamos la oferta con su relación mínima de empresa
        $oferta = Oferta::with('empresa:id,nombre')->find($id);
        
        if (!$oferta) {
            return response()->json([
                'success' => false,
                'message' => 'La oferta técnica solicitada no existe o ha sido eliminada.'
            ], 404);
        }
        
        return response()->json([
            'data' => $oferta,
            'message'=>'Detalle oferta cargada correctamente'
            ], 200);

    } catch (\Exception $e) {
        return response()->json([
            
            'message' => 'Error interno al obtener el detalle de la oferta.',
            'errors' => $e->getMessage()
        ], 500);
    }
   
}
// En InformeController.php (o AdminController)

/**
 * Obtener todos los demandantes con sus títulos asociados
 */
public function getAllAlumnos()
{
    try {
     $alumnos = Demandante::whereHas('user', function($query) {
            $query->where('validado', 1);
        })
        ->with(['titulos', 'user'])
        ->get()
        ->map(function ($alumno) {
            return [
                'id' => $alumno->id,
                'nombre' => $alumno->nombre . ' ' . $alumno->apellido,
                'email' => $alumno->user->email ?? 'Sin email',
                'validado' => $alumno->user->validado ?? 1, // Ya sabemos que es 1
                'titulos' => $alumno->titulos->pluck('nombre'),
                'telefono' => $alumno->telefono,
                'created_at' => $alumno->created_at 
            ];
        });
        return response()->json([
            'message' => 'Datos cargados correctamente',
            'data' => $alumnos
        ], 200);
    } catch (\Exception $e) {
        return response()->json([ 'message' => $e->getMessage()], 500);
    }
}

/**
 * Obtener todas las empresas con su estado de cuenta
 */
public function getAllEmpresas()
{
    try {
       $empresas = Empresa::whereHas('user', function($query) {
                $query->where('validado', 1);
            })
            ->with('user')
            ->get()
            ->map(function ($empresa) {
                return [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'cif' => $empresa->cif,
                    'email' => $empresa->user->email ?? 'Sin email',
                    'validado' => 1, // Sabemos que es 1 por el filtro whereHas
                    'telefono' => $empresa->telefono_contacto,
                    'web' => $empresa->web,
                    'created_at' => $empresa->created_at 
                ];
            });

        return response()->json([
            'message' => 'Datos cargados correctamente',
            'data' => $empresas
        ], 200);
    } catch (\Exception $e) {
        return response()->json([ 'message' => $e->getMessage()], 500);
    }
}
public function getDetalleAlumnoAdmin($id)
{
    try {
        // Buscar el demandante con todas sus relaciones necesarias
$alumno = Demandante::with([
    'user:id,email', 
    'titulos' => function($query) {
        // ESPECIFICAMOS LA TABLA EN EL ID: 'titulos.id'
        $query->select('titulos.id', 'titulos.nombre', 'titulos.nivele_id')
              ->with('nivel:id,nivel'); 
    }
])->find($id);
        if (!$alumno) {
            return response()->json([
                'success' => false,
                'message' => 'Alumno no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $alumno,
            'message' => 'Detalle del alumno cargado correctamente'
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el expediente del alumno.',
            'errors'  => $e->getMessage()
        ], 500);
    }
}
}
