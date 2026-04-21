<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\Empresa;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


/**
 * @OA\Tag(name="Validaciones", description="Gestión de aprobación de nuevos usuarios por parte del centro")
 */
class ValidacionController extends Controller
{
   /**
     * @OA\Get(
     * path="/api/usuarios/validaciones",
     * summary="Listar usuarios pendientes de validación",
     * tags={"Validaciones"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="busqueda", in="query", description="Filtrar por nombre o email", @OA\Schema(type="string")),
     * @OA\Parameter(name="rows", in="query", description="Número de registros por página", @OA\Schema(type="integer", default=10)),
     * @OA\Response(
     * response=200,
     * description="Listado obtenido correctamente",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="object"),
     * @OA\Property(property="message", type="string")
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        //
        try {
            $busqueda=$request->input('busqueda');
            $rows=$request->input('rows',10);
           $query = User::where('validado', 0)
            ->where('status', '!=', \App\Enums\UserEstado::INACTIVO->value)
            ->with('rol:id,rol');

        //Filtro de búsqueda (si el usuario escribe en el input de Angular)
        if (!empty($busqueda)) {
            $query->where(function($q) use ($busqueda) {
                $q->where('name', 'LIKE', "%{$busqueda}%")
                  ->orWhere('email', 'LIKE', "%{$busqueda}%");
            });
        }

        // 4. Ejecutamos la paginación
        $users = $query->select('id', 'name', 'email', 'validado', 'role_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($rows);
            return response()->json([
                'data' => $users,
                'message' => 'Listado de validaciones obtenido correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'data' => [],
                'message' => 'Error al obtener usuarios: ' 
            ], 500);
        }
    }

   
/**
     * @OA\Patch(
     * path="/api/usuarios/validaciones/{user}",
     * summary="Validar usuario y crear perfil",
     * tags={"Validaciones"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="user",
     * in="path",
     * required=true,
     * description="ID del usuario",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Validado correctamente"),
     * @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function update(User $user)
    {
        //


        try {
            return DB::transaction(function () use ($user) {
    // Registrar el ID del administrador que valida
                $centro = \App\Models\Centro::first();
                // Actualizar estado del usuario
                $user->validado = true;
                $user->status = \App\Enums\UserEstado::ACTIVO;
                $user->save();
// Crear perfil según el rol (2: Empresa, 3: Demandante) usando updateOrCreate para evitar duplicados
                if ($user->role_id == 2) {
                    $empresa = new Empresa();
                    $empresa->nombre = $user->name;
                    $empresa->user_id = $user->id;
                    $empresa->centro_id = $centro->id;
                    $empresa->save();
                } else if ($user->role_id == 3) {
                    $demandante = new Demandante();
                    $demandante->nombre = $user->name;
                    $demandante->centro_id = $centro->id;
                    $demandante->user_id = $user->id;
                    $demandante->save();
                }

                return response()->json([
                    'data' => $user,
                    'message' => 'Usuario validado correctamente y registrado'
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
                'message' => 'Error al validar: '
            ], 500);
        }
    }
/**
     * @OA\Delete(
     * path="/api/usuarios/validaciones/{user}",
     * summary="Rechazar y eliminar usuario",
     * tags={"Validaciones"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="user",
     * in="path",
     * required=true,
     * description="ID del usuario",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Eliminado correctamente")
     * )
     */
    public function destroy(User $user)
    {
        //
        try {
            $user->delete();

            return response()->json([
                'data' => null,
                'message' => 'Usuario eliminado del registro correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Error al eliminar: '
            ], 500);
        }
    }

/**
     * @OA\Get(
     * path="/api/usuarios/validaciones/pendientes",
     * summary="Contar validaciones pendientes",
     * tags={"Validaciones"},
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200, 
     * description="Conteo obtenido",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="integer", example=5),
     * @OA\Property(property="message", type="string")
     * )
     * )
     * )
     */
       public function getPendientesCount()
    {
        try {
            // Contamos usuarios (alumnos y empresas) con validado = 0
            $count = \App\Models\User::where('validado', 0)
                ->where('status', '!=', \App\Enums\UserEstado::INACTIVO->value)
                ->count();
            return response()->json([
                'data' => $count,
                'message' => 'Numero de usuarios por validar obtenido correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => 0, 'message' => 'Error al obtener el número de pendientes de  validar.'], 500);
        }
    }
}



