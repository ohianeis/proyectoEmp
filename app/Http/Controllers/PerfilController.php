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
        //
        $usuario = Auth::user();


        if ($usuario->role_id == 2) {
            $perfil = $usuario->empresa;
            $usuario->empresa->centro;
            $direccion = $usuario->empresa->direccion ? [
                'calle' => $usuario->empresa->direccion->calle,
                'numero' => $usuario->empresa->direccion->numero,
                'ciudad' => $usuario->empresa->direccion->ciudad,
            ] : ['direccion' => 'sin completar'];
        } else if ($usuario->role_id == 3) {

            $usuario->demandante->situacion; // Carga la relación de 'demandante' y su 'situacion'
            $usuario->demandante->centro;
            $perfil = $usuario->demandante;
            $direccion = $usuario->demandante->direccion ? [
                'calle' => $usuario->demandante->direccion->calle,
                'numero' => $usuario->demandante->direccion->numero,
                'ciudad' => $usuario->demandante->direccion->ciudad,
            ] : ['direccion' => 'sin completar'];
        }

        return response()->json([
            'perfil' => $perfil,
            'info' => [
                'direccion' => [
                    'updateDireccion' => '/perfil/direccion/{demandante/empresa}'
                ],
                'perfil' => [
                    'updatePerfil' => '/perfil/{demandante/empresa}'
                ]


            ]
        ], 200);
    }


    /**
     * Crear dirección si esta null ,la primera vez.
     */
    /**
     * @OA\Post(
     *     path="/api/perfil/direccion",
     *     summary="Crear dirección",
     *     description="Crea una dirección si actualmente no está asociada con la empresa o el demandante del usuario autenticado.",
     *     tags={"Perfil"},
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"linea1", "ciudad", "provincia", "codigoPostal", "visible"},
     *             @OA\Property(property="linea1", type="string", example="Avenida Siempre Viva 123", description="Primera línea de dirección."),
     *             @OA\Property(property="linea2", type="string", nullable=true, example="Apartamento 45", description="Segunda línea de dirección, opcional."),
     *             @OA\Property(property="ciudad", type="string", example="Pamplona", description="Ciudad."),
     *             @OA\Property(property="provincia", type="string", example="Navarra", description="Provincia."),
     *             @OA\Property(property="codigoPostal", type="integer", example=31600, description="Código postal, debe tener 6 dígitos."),
     *             @OA\Property(property="visible", type="boolean", example=true, description="Visibilidad de la dirección (true o false).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dirección creada correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Dirección creada correctamente")
     *         )
     *     ),
     *  @OA\Response(
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
     *         response=422,
     *         description="Errores de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="object", example={
     *                 "linea1": {"El campo linea1 es obligatorio."},
     *                 "ciudad": {"El campo ciudad es obligatorio."},
     *                 "codigoPostal": {"Debe tener exactamente 6 dígitos."}
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al procesar la solicitud.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        //
        try {

            $usuario = Auth::user();
            $direccion = $usuario->role_id == 2 ? $usuario->empresa->direccion : $usuario->demandante->direccion;

            if (is_null($direccion)) {



                $request->validate([
                    'linea1' => 'required|string|max:255',
                    'linea2' => 'nullable|string|max:255',
                    'ciudad' => 'required|string|max:100',
                    'provincia' => 'required|string|max:100',
                    'codigoPostal' => 'required|integer|digits:5',
                    'visible' => 'required|boolean'
                ]);

                $direccion = new Direccione();
                $direccion->linea1 = $request['linea1'];
                $direccion->linea2 = $request['linea2'];
                $direccion->ciudad = $request['ciudad'];
                $direccion->provincia = $request['provincia'];
                $direccion->codigoPostal = $request['codigoPostal'];
                $direccion->visible = $request['visible'];
                if ($usuario->role_id == 2) {
                    $direccion->direccioneable_id = $usuario->empresa->id;
                    $direccion->direccioneable_type = 'App\Models\Empresa';
                } else if ($usuario->role_id == 3) {

                    $direccion->direccioneable_id = $usuario->demandante->id;
                    $direccion->direccioneable_type = 'App\Models\Demandante';
                }
                $direccion->save();
                return response()->json([
                    'mensaje' => 'Dirección creada correctamente'
                ], 201);
            } else {
                return $this->actualizarDireccion($request, $direccion);
            }
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
     * Display the specified resource.
     */
    /**
     * @OA\Patch(
     *     path="/api/perfil/direccion/{direccion}",
     *     summary="Actualizar dirección",
     *     description="Actualiza una dirección existente asociada a la empresa o el demandante del usuario autenticado. Si la dirección no existe, redirige al método que la crea.",
     *     tags={"Perfil"},
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
     *     @OA\Parameter(
     *         name="direccion",
     *         in="path",
     *         description="ID de la dirección que se desea actualizar",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"linea1", "ciudad", "provincia", "codigoPostal", "visible"},
     *             @OA\Property(property="linea1", type="string", example="Calle Mayor 123", description="Primera línea de dirección."),
     *             @OA\Property(property="linea2", type="string", nullable=true, example="Piso 4B", description="Segunda línea de dirección, opcional."),
     *             @OA\Property(property="ciudad", type="string", example="Pamplona", description="Ciudad."),
     *             @OA\Property(property="provincia", type="string", example="Navarra", description="Provincia."),
     *             @OA\Property(property="codigoPostal", type="integer", example=31600, description="Código postal con 5 dígitos."),
     *             @OA\Property(property="visible", type="boolean", example=true, description="Indica si la dirección es visible (true o false).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dirección actualizada correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Dirección actualizada correctamente.")
     *         )
     *     ),
     *  @OA\Response(
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
     *         description="Errores de validación en los datos proporcionados.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="object", example={
     *                 "linea1": {"El campo linea1 es obligatorio."},
     *                 "ciudad": {"El campo ciudad es obligatorio."},
     *                 "codigoPostal": {"Debe tener exactamente 5 dígitos."}
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al procesar la solicitud.")
     *         )
     *     )
     * )
     */

    public function actualizarDireccion(Request $request, Direccione $direccion)
    {


        if (is_null($direccion)) {
            return $this->store($request);
        }
        try {
            $validacion = $request->validate([
                'linea1' => 'required|string|max:255',
                'linea2' => 'nullable|string|max:255',
                'ciudad' => 'required|string|max:100',
                'provincia' => 'required|string|max:100',
                'codigoPostal' => 'required|integer|digits:5',
                'visible' => 'required|boolean'
            ]);
            //  $direccion = Direccione::find($usuario);

            // Obtener los campos actuales de la dirección
            $camposActuales = $direccion->only(['linea1', 'linea2', 'ciudad', 'provincia', 'codigoPostal', 'visible']);

            // Comparar los datos validados con los actuales
            $diferencias = array_diff_assoc($validacion, $camposActuales);

            // Verificar si hay cambios
            if (empty($diferencias)) {
                return response()->json([
                    'mensaje' => 'No se realizaron cambios porque los datos son idénticos.'
                ], 200);
            }
            foreach ($validacion as $dato => $valor) {
                $direccion->$dato = $valor;
            }
            $direccion->save();
            return response()->json([
                'mensaje' => 'Direccion actualizada correctamente'
            ], 201);
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
        //
        try {
            $rol = Auth::user()->role_id;
            $usuario = Auth::user(); //controlar quien es el usuario un demandante o una empresa

            if ($rol == 2) {
                $idUsuario = $usuario->empresa->id;
                $empresa = Empresa::find($idUsuario);
                $validacion = $request->validate([
                    'cif' => 'nullable|string|size:9|unique:empresas,cif,' . $empresa->id,
                    'nombre' => 'required|string|regex:/^[a-zA-Z\s.]+$/|max:255',
                    'localidad' => 'nullable|string|max:100'
                ]);

                if (isset($validacion['cif'])) {

                    $empresa->cif = $request['cif'];
                }
                if (isset($validacion['nombre'])) {

                    $empresa->nombre = $request['nombre'];
                }
                if (isset($validacion['localidad'])) {

                    $empresa->localidad = $request['localidad'];
                }
                $empresa->save();
            } else if ($rol == 3) {
                $idUsuario = $usuario->demandante->id;
                $validacion = $request->validate([
                    'nombre' => 'required|string|regex:/^[a-zA-Z\s]+$/|max:100',
                    'telefono' => 'nullable|integer|regex:/^(\34\s)?([6789][0-9]{8})$/',
                    'experienciaLaboral' => 'nullable|string|max:2000',
                    'situacion' => 'nullable|integer|exists:situaciones,id'
                ]);
                $demandante = Demandante::find($idUsuario);
                if (isset($validacion['nombre'])) {

                    $demandante->nombre = $request['nombre'];
                }
                if (isset($validacion['telefono'])) {

                    $demandante->telefono = $request['telefono'];
                }
                if (isset($validacion['experienciaLaboral'])) {

                    $demandante->experienciaLaboral = $request['experienciaLaboral'];
                }
                if (isset($validacion['situacion'])) {

                    $demandante->situacione_id = $request['situacion'];
                }
                $demandante->save();
            }
            return response()->json([
                'mensaje' => 'Perfil actualizado correctamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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
