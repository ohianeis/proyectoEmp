<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\DemandanteTitulo;
use App\Models\Nivele;
use App\Models\Titulo;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TituloController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/titulos",
     *     summary="Obtener todos los títulos",
     *     description="Devuelve una lista de todos los títulos con su estado (activo/inactivo) y su nivel correspondiente.",
     *     tags={"Títulos"},
     *     security={
     *         {"sanctum": {}}
     *     },
     *  @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de títulos recuperada con éxito.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="titulo", type="string", example="Fontanería"),
     *                     @OA\Property(property="estado", type="string", enum={"activo", "inactivo"}, example="activo"),
     *                     @OA\Property(property="nivel", type="string", example="grado básico")
     *                 }
     *             )
     *         )
     *     ),
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
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     *  *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Error al procesar la solicitud."),
     *                 @OA\Property(property="message", type="string", example="Detalles del error interno.")
     *             }
     *         )
     *     )
     * )
     * )
     */
    public function index()
    {


        //listar todos los titulos
        try {
            $titulos = \App\Models\Titulo::with('nivel')
                ->orderBy('nivele_id')
                ->get()
                ->map(function ($titulo) {
                    return [
                        'id' => $titulo->id,
                        'titulo' => $titulo->nombre,
                        'estado' => $titulo->activado ? 'activo' : 'inactivo',
                        'nivel' => $titulo->nivel->nivel,
                    ];
                });
      
            return response()->json($titulos,200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ],500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/titulos/niveles/listado",
     *     summary="Obtener niveles de títulos",
     *     description="Devuelve una lista de niveles de títulos almacenados en la base de datos.",
     *     tags={"Títulos"},
     *     security={
     *         {"sanctum": {}}
     *     },
     *  @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de niveles de títulos obtenida correctamente.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="ID del nivel."),
     *                 @OA\Property(property="nivel", type="string", example="Grado básico", description="Nombre del nivel.")
     *             )
     *         )
     *     ),
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
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener los niveles.")
     *         )
     *     )
     * )
     */

    public function nivel()
    {
        try {
            $nivelesTitulos = Nivele::select('id', 'nivel')->get();
            return response()->json([$nivelesTitulos], 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/titulos/{titulo}",
     *     summary="Obtener detalles de un título específico",
     *     description="Devuelve los detalles de un título, incluyendo su nivel y el nombre del centro asociado. Excluye el campo 'activado' y formatea las fechas.",
     *     tags={"Títulos"},
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
     *     @OA\Parameter(
     *         name="titulo",
     *         in="path",
     *         required=true,
     *         description="ID del título a recuperar",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del título recuperados con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Desarrollo de aplicaciones web"),
     *                 @OA\Property(property="nivel", type="string", example="Grado superior"),
     *                 @OA\Property(property="centro", type="string", example="Centro de Formación Profesional"),
     *                 @OA\Property(property="created_at", type="string", example="19-03-2025 07:00:06"),
     *                 @OA\Property(property="updated_at", type="string", example="19-03-2025 07:00:06")
     *             }
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
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
     *         description="Recurso no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Recurso no encontrado.")
     *         )
     *     )
     * )
     */
    public function show(Titulo $titulo)
    {
        try{

        
        // Cargar las relaciones 'nivel' y 'centro'
        $titulo->load('nivel', 'centro');


        $response = [
            'id' => $titulo->id,
            'nombre' => $titulo->nombre,
            'nivel' => [
                'id' => $titulo->nivel->id,
                'nivel' => $titulo->nivel->nivel, // Campo 'nivel' desde la relación
                'url' => "/api/niveles",
            ],
            'centro' => [
                'id' => $titulo->centro->id,
                'nombre' => $titulo->centro->nombre, // Campo 'nombre' desde la relación 'centro'

            ],
            'created_at' => $titulo->created_at->format('d-m-Y'), // Formato de fecha legible
            'updated_at' => $titulo->updated_at->format('d-m-Y')  // Formato de fecha legible
        ];

        return response()->json($response,200);
    }catch (Exception $e) {
        return response()->json([
            'mensaje' => $e->getMessage()
        ], 500);
    } 
}
    
    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/titulos",
     *     summary="Crear un nuevo título",
     *     description="Crea un nuevo título en la base de datos. Requiere autorización mediante Sanctum.",
     *     tags={"Títulos"},
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
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para crear un nuevo título",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="nombre", type="string", example="Ingeniería de software", description="Nombre del título."),
     *                 @OA\Property(property="nivel", type="integer", example=2, description="ID del nivel asociado. Debe existir en la tabla niveles."),
     *                 @OA\Property(property="centro", type="integer", example=5, description="ID del centro asociado. Debe existir en la tabla centros.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Título creado correctamente.",
     *         @OA\JsonContent(
     *             type="string",
     *             example="Titulo creado correctamente"
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
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
     *         response=409,
     *         description="Conflicto: el título ya existe.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="mensaje", type="string", example="Título ya existente")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(
     *                     property="errors",
     *                     type="object",
     *                     example={
     *                         "nombre": {"El campo nombre es obligatorio."},
     *                         "nivel": {"El nivel no existe en la tabla niveles."},
     *                         "centro": {"El centro no existe en la tabla centros."}
     *                     }
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Error al crear el título"),
     *                 @OA\Property(property="message", type="string", example="Detalles del error interno.")
     *             }
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        //
        $existeTitulo = Titulo::where('nombre', $request->nombre)->where('nivele_id', $request->nivel)->exists();
        if ($existeTitulo) {
            return response()->json([
                'mensaje' => 'Título ya existente',
            ], 409);
        } else {
            try {
                $validacion = $request->validate([
                    'nombre' => 'required|string|max:255',
                    'nivel' => 'required|integer|exists:niveles,id', //exista en la tabla niveles el dato
                    'centro' => 'required|integer|exists:centros,id',

                ]);
                $nuevoRegistro = [];
                if ($validacion) {
                    $nuevoRegistro['nombre'] = strtolower($request['nombre']);
                    $nuevoRegistro['activado'] = 1;
                    $nuevoRegistro['nivele_id'] = $request['nivel'];
                    $nuevoRegistro['centro_id'] = $request['centro'];
                }
                Titulo::create($nuevoRegistro);
                return response()->json('Titulo creado correctamente', 201);
            } catch (ValidationException $e) {
                return response()->json($e->errors(), 422);
            } catch (Exception $e) {
                return response()->json([
                    'error' => 'Error al crear el título',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }
    /**
     * @OA\Get(
     *     path="/api/titulos/activos",
     *     summary="Obtener títulos activados ordenados por nombre",
     *     description="Devuelve una lista de todos los títulos que están activados (activado=1) y ordenados alfabéticamente por su nombre.",
     *     tags={"Títulos-Activos"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Títulos activados recuperados con éxito.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Fontanería"),
     *                     @OA\Property(property="activado", type="integer", example=1),
     *                     @OA\Property(property="nivele_id", type="integer", example=1),
     *                     @OA\Property(property="centro_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", example="2025-03-19 07:00:06"),
     *                     @OA\Property(property="updated_at", type="string", example="2025-03-19 07:00:06")
     *                 }
     *             )
     *         )
     *     ),
     * 
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
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *      @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Error al procesar la solicitud."),
     *                 @OA\Property(property="message", type="string", example="Detalles del error interno.")
     *             }
     *         )
     *     )
     * )
     * )
     */
    public function titulosActivos()
    {
        try {
            $titulos = Titulo::select('nombre')->where('activado', 1)->orderBy('nombre')->get();
            return response()->json($titulos,200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ],500);
        }
    }
  

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Patch(
     *     path="/api/titulos/{titulo}",
     *     summary="Actualizar un título",
     *     description="Actualiza los datos de un título específico, incluyendo el nombre, nivel y centro. Requiere autorización mediante Sanctum.",
     *     tags={"Títulos"},
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
     *     @OA\Parameter(
     *         name="titulo",
     *         in="path",
     *         required=true,
     *         description="ID del título a actualizar",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para actualizar el título",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="nombre", type="string", example="Fontanería", description="Nombre del título."),
     *                 @OA\Property(property="nivel", type="integer", example=1, description="ID del nivel asociado. Debe existir en la tabla niveles."),
     *                 @OA\Property(property="centro", type="integer", example=1, description="ID del centro asociado. Debe existir en la tabla centros.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Título actualizado correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Fontanería actualizado"),
     *                 @OA\Property(property="nivele_id", type="integer", example=2),
     *                 @OA\Property(property="centro_id", type="integer", example=5),
     *                 @OA\Property(property="updated_at", type="string", example="19-03-2025 07:30:00")
     *             }
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
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
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(
     *                     property="errores",
     *                     type="object",
     *                     example={
     *                         "id": {"El campo id no está en la lista de valores permitidos"},
     *                         "nombre": {"El campo nombre es obligatorio."},
     *                         "nivel": {"El campo nivel no existe."},
     *                         "centro": {"El campo centro no existe."}
     *                     }
     *                 )
     *             }
     *         )
     *     ),
     *      @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Error al procesar la solicitud."),
     *                 @OA\Property(property="message", type="string", example="Detalles del error interno.")
     *             }
     *         )
     *     )
     * )
     * )
     */
    public function update(Request $request, Titulo $titulo)
    {
        //
        try {
            $validacion = $request->validate([
                'id' => 'integer|in:' . $titulo->id, //sea el mismo que el id a actualizar no se haya cambiado
                'nombre' => 'required|string|max:255',
                'nivel' => 'required|integer|exists:niveles,id', //exista en la tabla niveles el dato
                'centro' => 'required|integer|exists:centros,id',

            ]);
            //actualizar datos
            if (isset($validacion['nombre'])) {
                $titulo->nombre = strtolower($validacion['nombre']);
            }
            if (isset($validacion['nivel'])) {
                $titulo->nivele_id = $validacion['nivel'];
            }
            if (isset($validacion['centro'])) {
                $titulo->centro_id = $validacion['centro'];
            }
            $titulo->save();
            return response()->json($titulo, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'errores' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'error al actualizar el titulo',
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/api/titulos/{titulo}",
     *     summary="Eliminar un título",
     *     description="Elimina un título de la base de datos. Si tiene ofertas asociadas abiertas, cambia su estado a inactivo en lugar de borrarlo.",
     *     tags={"Títulos"},
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
     *     @OA\Parameter(
     *         name="titulo",
     *         in="path",
     *         required=true,
     *         description="ID del título a eliminar",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Operación exitosa.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="mensaje", type="string", example="No se puede borrar el título porque tiene ofertas asociadas a él. Se ha pasado a estado inactivo"),
     *                 @OA\Property(property="mensage", type="string", example="titulo borrado correctamente")
     *             }
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
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
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado."),
     *                 @OA\Property(property="mensaje", type="string", example="El título especificado no existe.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="error al borrar el titulo"),
     *                 @OA\Property(property="mensaje", type="string", example="Detalles del error.")
     *             }
     *         )
     *     )
     * )
     */
    public function destroy(Titulo $titulo)
    {
        //


        //comprobar si el titulo esta siendo usado por alguna oferta de trabajo
        $ofertasAbiertas = $titulo->ofertas()->where('estado_id', 1)->exists();
        try {
            if ($ofertasAbiertas) {
                $titulo->activado = 0;
                $titulo->save();
                return response()->json([
                    'mensaje' => 'No se puede borrar el título porque tiene ofertas asociadas a él. Se ha pasado a estado inactivo'
                ], 200);
            }
            $titulo->delete();
            return response()->json([
                'mensage' => 'titulo borrado correctamente',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'error al borrar el titulo',
                'mensaje' => $e->getMessage()
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/titulos/demandante",
     *     summary="Asocia títulos al demandante autenticado",
     *     description="Este endpoint permite asociar uno o varios títulos al demandante actual.",
     *     tags={"Títulos-Demandante"},
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
     *@OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *        type="object",
     *        properties={
     *           @OA\Property(
     *               property="titulos",
     *               type="array",
     *               description="Lista de títulos con sus atributos",
     *              @OA\Items(
     *                  type="object",
     *                  properties={
     *                      @OA\Property(property="id", type="integer", description="ID del título", example=1),
     *                      @OA\Property(property="centro", type="integer", description="ID del centro", example=101),
     *                      @OA\Property(property="anio", type="integer", description="Año de creación del título", example=2020),
     *                     @OA\Property(property="cursando", type="boolean", description="Si el demandante está cursando el título", example=true)
     *                 }
     *             )
     *         )
     *     },
     * )
     *),
     *     @OA\Response(
     *         response=201,
     *         description="Títulos asociados correctamente",
     *         @OA\JsonContent(
     *             example={
     *                 "mensaje": "Titulo/s creados correctamente"
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Todos los títulos ya están vinculados al demandante",
     *         @OA\JsonContent(
     *             example={
     *                 "mensaje": "Todos los títulos ya están vinculados al demandante."
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(
     *                     property="errores",
     *                     type="object",
     *                     example={
     *                         "titulos.0.id": {"El id del título no existe en la lista de valores permitidos."},
     *                         "titulos.1.año": {"El año debe estar entre 1900 y 2025."},
     *                         "titulos.2.centro": {"El campo centro es obligatorio."}
     *                     }
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             example={
     *                 "mensaje": "Error interno del servidor."
     *             }
     *         )
     *     )
     * )
     */
    public function agregarTitulos(Request $request)
    {
        $demandante = Demandante::where('user_id', Auth::user()->id)->first();
        try {
            $validacion = $request->validate([
                'titulos' => 'required|array',
                'titulos.*.id' => 'exists:titulos,id', // Validar que existe el ID en la tabla titulos
                'titulos.*.centro' => 'required|exists:centros,id', // Validar que existe el centro
                'titulos.*.año' => 'required|integer|min:1900|max:' . date('Y'), // Validar año válido
                'titulos.*.cursando' => 'required|boolean', // Validar cursando como booleano
            ]);

            $titulosNoDuplicados = collect($validacion['titulos'])->filter(function ($titulo) use ($demandante) {
                return !DemandanteTitulo::where('demandante_id', $demandante->id)
                    ->where('titulo_id', $titulo['id'])
                    ->exists(); // Comprobar si el título ya está asociado
            });

            if ($titulosNoDuplicados->isEmpty()) {
                return response()->json('Todos los títulos ya están vinculados al demandante.', 400);
            }

            foreach ($titulosNoDuplicados as $titulo) {
                $demandante->titulos()->attach($titulo['id'], [
                    'centro' => $titulo['centro'],
                    'año' => $titulo['año'],
                    'cursando' => $titulo['cursando'],
                ]);
            }
            return response()->json('Titulo/s creados correctamente', 201);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/titulos/demandante",
     *     summary="Obtener títulos del demandante autenticado",
     *     description="Devuelve los títulos asociados al demandante actualmente autenticado mediante Sanctum.",
     *     tags={"Títulos-Demandante"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="29|nqrBfqXOPVLqKfOwZSa6uvpWMxWwz9UQYHcOzSCgce17c3cf"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de títulos asociados al demandante autenticado.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Título 1"),
     *                     @OA\Property(property="centro", type="string", example="Universidad de Navarra"),
     *                     @OA\Property(property="año", type="integer", example=2023),
     *                     @OA\Property(property="cursando", type="boolean", example=1)
     *                 }
     *             )
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
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
     *         description="recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="El demandante no existe.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Error al procesar la solicitud."),
     *                 @OA\Property(property="message", type="string", example="Detalles del error interno.")
     *             }
     *         )
     *     )
     * )
     */

    public function titulosDemandante()
    {
        $demandante = Demandante::where('user_id', Auth::user()->id)->first();

        try {
            return response()->json($demandante->titulos, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Delete(
     *     path="/api/titulos/demandante/{id}",
     *     summary="Eliminar un título del demandante autenticado",
     *     description="Elimina un título asociado al demandante actualmente autenticado mediante Sanctum.",
     *     tags={"Títulos-Demandante"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 29|nqrBfqXOPVLqKfOwZSa6uvpWMxWwz9UQYHcOzSCgce17c3cf"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del título que se desea eliminar",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="El título ha sido eliminado exitosamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="mensaje", type="string", example="El título ha sido eliminado.")
     *             }
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El título no está asociado al demandante.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="mensaje", type="string", example="El título no está asociado al demandante.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
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
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Error al procesar la solicitud."),
     *                 @OA\Property(property="message", type="string", example="Detalles del error interno.")
     *             }
     *         )
     *     )
     * )
     */

    public function tituloDemandante(Request $request)
    {

        try {
            $demandante = Demandante::where('user_id', Auth::user()->id)->first();


            // Obtener el ID del título desde el request
            $id = $request->id;


            $registro = DemandanteTitulo::where('id', $id)
                ->where('demandante_id', $demandante->id)
                ->first();

            if ($registro) {
                $registro->delete(); // Eliminar exclusivamente de la tabla pivot
                return response()->json(['mensaje' => 'El título ha sido eliminado del demandante.'], 201);
            }

            return response()->json(['mensaje' => 'Recurso no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ]);
        }
    }
}
