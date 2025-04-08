<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\Empresa;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidacionController extends Controller
{
    /**
     * empresas y demandantes a validar listado
     */
    /**
     * @OA\Get(
     *     path="/api/usuarios/validaciones",
     *     summary="Obtiene los usuarios no validados",
     *     description="Este endpoint devuelve la lista de usuarios que no están validados, junto con su rol ordenados por fecha registro.",
     *     tags={"Validaciones"},
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
     *         description="Lista de usuarios no validados",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="ID del usuario"),
     *                 @OA\Property(property="name", type="string", example="Empresa", description="Nombre del usuario"),
     *                 @OA\Property(property="email", type="string", example="empresa@example.com", description="Correo electrónico"),
     *                 @OA\Property(property="validado", type="integer", example=0, description="Estado de validación del usuario"),
     *                 @OA\Property(property="role_id", type="integer", example=2, description="ID del rol asociado"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="29/03/2025", description="Fecha de creación del usuario")
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
        try {
            $users = User::where('validado', 0)
                ->select('id', 'name', 'email', 'validado', 'role_id', 'created_at')
                ->with('rol:id,rol') // Carga la relación para incluir el nombre del rol
                ->orderBy('created_at', 'asc') // Ordena por la fecha de creación en orden ascendente
                ->get();
            return response()->json($users, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * valida al usuario
     */
    /**
     * @OA\Patch(
     *     path="/api/usuarios/validaciones/{user}",
     *     summary="Valida un usuario y lo registra como Empresa o Demandante",
     *     description="Este endpoint valida a un usuario y lo registra en la tabla correspondiente (Empresa o Demandante) según su rol.",
     *     tags={"Validaciones"},
     *        security={{"sanctum": {}}},
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
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID del usuario que será validado",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario validado correctamente y registrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Usuario validado correctamente y registrado")
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
     *       @OA\Response(
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
    public function update(User $user)
    {
        //


        try {
            $centro = Auth::user()->id;
            $user->validado = true;
            $user->save();
            if ($user->role_id == 2) {
                $empresa=new Empresa();
                $empresa->nombre=$user->name;
                $empresa->user_id=$user->id;
                $empresa->centro_id=$centro;
             /*   Empresa::create([
                    'nombre' => ,
                    'user_id' => ,
                    'centro_id' => 
                ]);*/
                $empresa->save();
            } else if ($user->role_id == 3) {
                $demandante=new Demandante();
                $demandante->nombre=$user->name;
                $demandante->centro_id=$centro;
                $demandante->user_id=$user->id;

             /*   Demandante::create([
                    'nombre' => $user->name,
                    'centro_id' => $centro,
                    'user_id' => $user->id
                ]);*/
                $demandante->save();
            }
            return response()->json([
                'mensaje' => 'Usuario validado correctamente y registrado'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * No se acepta la validacion
     */
    /**
 * @OA\Delete(
 *     path="/api/usuarios/validaciones/{user}",
 *     summary="Elimina un usuario del registro",
 *     description="Este endpoint elimina a un usuario del sistema. Se utiliza cuando no se acepta su validación.",
 *     tags={"Validaciones"},
 *        security={{"sanctum": {}}},
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
 *         name="user",
 *         in="path",
 *         required=true,
 *         description="ID del usuario que será eliminado",
 *         @OA\Schema(
 *             type="integer",
 *             example=1
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Usuario eliminado del registro correctamente",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="mensaje", type="string", example="Usuario eliminado del registro correctamente")
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
 *         description="Usuario no encontrado. Verifica el ID proporcionado.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Usuario no encontrado.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="mensaje", type="string", example="Error al eliminar el usuario. Por favor, inténtelo de nuevo.")
 *         )
 *     )
 * )
 */
    public function destroy(User $user)
    {
        //
        try{
           $user->delete();
           return response()->json([
            'mensaje'=>'Usuario eliminado del resgistro correctamente'
           ],200);
        }catch(Exception $e){
            return response()->json([
                'mensaje'=>$e->getMessage()
            ],500);
        }
    }
}
