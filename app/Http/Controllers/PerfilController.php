<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\Direccione;
use App\Models\Empresa;
use App\Models\Situacione;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationData;

use function PHPUnit\Framework\isEmpty;

class PerfilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/perfil",
     *     summary="Obtiene el perfil del usuario autenticado",
     *     description="Este endpoint devuelve el perfil del usuario autenticado basado en su rol. Si el usuario tiene un rol de Empresa, devolverá su información como Empresa. Si el rol es Demandante, devolverá su información como Demandante.",
     *     tags={"Perfil"},
     *     security={
     *         {"sanctum": {}}
     *     },
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
     *     @OA\Response(
     *         response=200,
     *         description="Perfil del usuario autenticado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="perfil",
     *                 type="object",
     *                 description="Datos del perfil del usuario",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="cif", type="string", nullable=true, example=null),
     *                 @OA\Property(property="nombre", type="string", example="empresa"),
     *                 @OA\Property(property="localidad", type="string", nullable=true, example=null),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="centro_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date", example="30/03/2025"),
     *                 @OA\Property(property="updated_at", type="string", format="date", example="30/03/2025"),
     *                 @OA\Property(
     *                     property="direccion",
     *                     type="object",
     *                     nullable=true,
     *                     description="Dirección asociada al perfil (puede ser null si no hay dirección disponible)",
     *                     @OA\Property(property="linea1", type="string", example="probando direccion"),
     *                     @OA\Property(property="linea2", type="string", nullable=true, example=null),
     *                     @OA\Property(property="ciudad", type="string", example="pamplona"),
     *                     @OA\Property(property="provincia", type="string", example="navarra"),
     *                     @OA\Property(property="codigoPostal", type="integer", example=31600),
     *                     @OA\Property(property="visible", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date", example="30/03/2025"),
     *                     @OA\Property(property="updated_at", type="string", format="date", example="30/03/2025")
     *                 )
     *             )
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
     *         response=404,
     *         description="Recurso no encontrado. La ruta solicitada no existe.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Recurso no encontrado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Ocurrió un error inesperado. Por favor, inténtalo nuevamente.")
     *         )
     *     )
     * )
     */
    public function index()
    {


        try {
            /** @var \App\Models\User $usuario */
            $usuario = Auth::user();
            $perfil = null;

            if ($usuario->role_id == 2) {
                $perfil = $usuario->empresa()->with(['direccion', 'centro'])->first();
            } else if ($usuario->role_id == 3) {
                $perfil = $usuario->demandante()->with(['direccion', 'situacion', 'centro'])->first();
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil cargado con éxito',
                'data'    => $perfil
            ], 200);
        } catch (Exception $e) {
            // Si algo falla, el Frontend recibe un mensaje claro en lugar de un error de sistema
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el perfil',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Post(
     * path="/api/perfil/direccion",
     * summary="Guardar o actualizar dirección del perfil",
     * description="Crea una nueva dirección si el usuario no tiene una, o actualiza la existente si ya tiene una asociada (Empresa o Demandante). La identificación se realiza mediante el token de usuario.",
     * tags={"Perfil"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * type="object",
     * required={"linea1", "ciudad", "provincia", "codigoPostal", "visible"},
     * @OA\Property(property="linea1", type="string", example="Calle Mayor 123", description="Dirección principal."),
     * @OA\Property(property="linea2", type="string", nullable=true, example="Piso 4B", description="Información adicional opcional."),
     * @OA\Property(property="ciudad", type="string", example="Pamplona", description="Localidad o ciudad."),
     * @OA\Property(property="provincia", type="string", example="Navarra", description="Provincia."),
     * @OA\Property(property="codigoPostal", type="string", example="31001", description="Código postal (se guarda como string)."),
     * @OA\Property(property="visible", type="boolean", example=true, description="Define si la dirección es pública para otros usuarios.")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Dirección creada correctamente",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="mensaje", type="string", example="Dirección creada correctamente")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Dirección actualizada correctamente",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="mensaje", type="string", example="Dirección actualizada correctamente")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=false),
     * @OA\Property(property="mensaje", type="object")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Error interno del servidor",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=false),
     * @OA\Property(property="mensaje", type="string")
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        try {
            $usuario = Auth::user();

            // Buscamos si ya tiene dirección (Empresa o Demandante)
            $direccion = ($usuario->role_id == 2)
                ? $usuario->empresa->direccion
                : $usuario->demandante->direccion;

            // Validamos los datos (común para crear y editar)
            $validacion = $request->validate([
                'linea1'       => 'required|string|max:255',
                'linea2'       => 'nullable|string|max:255',
                'ciudad'       => 'required|string|max:100',
                'provincia'    => 'required|string|max:100',
                'codigoPostal' => 'required|string|max:10',
                'visible'      => 'required|boolean'
            ]);

            if (is_null($direccion)) {
                // CASO 1: CREAR (Store)
                $direccion = new Direccione($validacion);

                if ($usuario->role_id == 2) {
                    $usuario->empresa->direccion()->save($direccion);
                } else {
                    $usuario->demandante->direccion()->save($direccion);
                }

                return response()->json(['message' => 'Dirección creada correctamente'], 201);
            } else {
                // CASO 2: ACTUALIZAR
                // Pasamos los datos validados al método de actualización
                return $this->actualizarDireccion($validacion, $direccion);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (Exception $e) {
            return response()->json([

                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function actualizarDireccion(array $datos, Direccione $direccion)
    {
        try {
            // Comparamos para ver si hay cambios reales
            $camposActuales = $direccion->only(['linea1', 'linea2', 'ciudad', 'provincia', 'codigoPostal', 'visible']);
            $diferencias = array_diff_assoc($datos, $camposActuales);

            if (empty($diferencias)) {
                return response()->json(['message' => 'No hay cambios que guardar.'], 200);
            }

            $direccion->update($datos);

            return response()->json(['message' => 'Dirección actualizada correctamente'], 200);
        } catch (Exception $e) {
            return response()->json([

                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Patch(
     *     path="/api/perfil/editar",
     *     summary="Actualizar el perfil del usuario autenticado",
     *     description="Actualiza los datos del perfil del usuario según su rol. Si es una empresa, actualiza sus datos. Si es un demandante, actualiza los datos relacionados al demandante. Quitar array empesa o demandante es solo para el ejemplo",
     *     tags={"Perfil"},
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
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         description="Datos para actualizar el perfil, dependiendo del rol del usuario.",
     *         @OA\Property(
     *             property="cif",
     *             type="string",
     *             nullable=true,
     *             example="B12345678",
     *             description="CIF único de la empresa (solo si el usuario es empresa)"
     *         ),
     *         @OA\Property(
     *             property="nombre",
     *             type="string",
     *             example="Mi Empresa S.L. o Juan Pérez",
     *             description="Nombre de la empresa o demandante"
     *         ),
     *         @OA\Property(
     *             property="localidad",
     *             type="string",
     *             nullable=true,
     *             example="Pamplona",
     *             description="Localidad de la empresa (solo para empresas)"
     *         ),
     *         @OA\Property(
     *             property="telefono",
     *             type="string",
     *             nullable=true,
     *             example="678123456",
     *             description="Teléfono del demandante (solo para demandantes)"
     *         ),
     *         @OA\Property(
     *             property="experienciaLaboral",
     *             type="string",
     *             nullable=true,
     *             example="10 años de experiencia en desarrollo web",
     *             description="Descripción de la experiencia laboral del demandante"
     *         ),
     *         @OA\Property(
     *             property="situacion",
     *             type="integer",
     *             nullable=true,
     *             example=3,
     *             description="ID de la situación actual del demandante (debe existir en la tabla situaciones)"
     *         ),
     *         example={
     *             "empresa": {
     *                 "cif": "B12345678",
     *                 "nombre": "Mi Empresa S.L.",
     *                 "localidad": "Pamplona"
     *             },
     *             "demandante": {
     *                 "nombre": "Juan Pérez",
     *                 "telefono": "678123456",
     *                 "experienciaLaboral": "10 años de experiencia en desarrollo web",
     *                 "situacion": 3
     *             }
     *         }
     *     )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Perfil actualizado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Perfil actualizado correctamente")
     *         )
     *     ),
     *    @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     *    @OA\Response(
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
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Error en la actualización: Detalles del error")
     *         )
     *     )
     * )
     */
  public function update(Request $request)
{
    try {
        $usuario = Auth::user();
        $rol = $usuario->role_id;
        $perfil = null;
        $validacion = [];

        if ($rol == 2) {
            $perfil = $usuario->empresa;
            $validacion = $request->validate([
                'nombre'            => 'required|string|max:255',
                'cif'               => 'nullable|string|max:15|unique:empresas,cif,' . $perfil->id,
                'localidad'         => 'nullable|string|max:100',
                'descripcion'       => 'nullable|string|max:2000',
                'web'               => 'nullable|string|max:255',
                'telefono_contacto' => 'nullable|string|max:20'
            ]);

            // CAMPOS A COMPARAR PARA EMPRESA
            $camposActuales = $perfil->only(['nombre', 'cif', 'localidad', 'descripcion', 'web', 'telefono_contacto']);
            
        } else if ($rol == 3) {
            $perfil = $usuario->demandante;
            $validacion = $request->validate([
                'nombre'             => 'required|string|max:100',
                'telefono'           => 'nullable|string|max:20',
                'experienciaLaboral' => 'nullable|string|max:2000',
                'situacion'          => 'nullable|integer|exists:situaciones,id'
            ]);

            // Mapeo manual para demandante (situacion vs situacione_id), lo hago aqui por relacion situacion_id
            $camposActuales = [
                'nombre'             => $perfil->nombre,
                'telefono'           => $perfil->telefono,
                'experienciaLaboral' => $perfil->experienciaLaboral,
                'situacion'          => $perfil->situacione_id
            ];
        }

        // CONTROL DE CAMBIOS: Comparamos lo que llega con lo que hay
        $diferencias = array_diff_assoc($validacion, $camposActuales);

        if (empty($diferencias)) {
            return response()->json([
           
                'message' => 'No hay cambios que guardar'
            ], 200); // Enviamos 200 para que Angular lo trate como éxito pero con mensaje de aviso
        }

        // Si hay cambios, guardamos
        if ($rol == 2) {
            $perfil->fill($validacion);
        } else {
            $perfil->fill([
                'nombre'             => $validacion['nombre'],
                'telefono'           => $validacion['telefono'],
                'experienciaLaboral' => $validacion['experienciaLaboral'],
                'situacione_id'      => $validacion['situacion'],
            ]);
        }
        
        $perfil->save();

        return response()->json([
     
            'message' => 'Perfil actualizado correctamente',
      
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
       
            'message' => collect($e->errors())->flatten()->first()
        ], 422);
    } catch (Exception $e) {
        return response()->json([
    
            'message' => 'Error al actualizar el perfil',
            'errors'  => $e->getMessage()
        ], 500);
    }
}
    /**
     * @OA\Get(
     *     path="/api/perfil/situaciones",
     *     summary="Obtener todas las situaciones disponibles",
     *     description="Devuelve una lista con las situaciones posibles de los demandantes, como 'Desempleado', 'Empleado', 'En búsqueda activa'.",
     *     operationId="listarSituaciones",
     *     tags={"Perfil"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de situaciones obtenida con éxito",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Desempleado")
     *             )
     *         )
     *     ),
     *    @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     *    @OA\Response(
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
     *         response=500,
     *         description="Error interno al obtener las situaciones",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Error al obtener las situaciones."),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[42000]: Syntax error...")
     *         )
     *     )
     * )
     */
    public function listarSituaciones()
    {
        try {
            $situaciones = Situacione::obtenerTodas();
            return response()->json($situaciones, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener las situaciones.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
