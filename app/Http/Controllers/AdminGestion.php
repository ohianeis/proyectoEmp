<?php

namespace App\Http\Controllers;

use App\Enums\UserEstado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;

/**
 * @OA\Tag(name="Gestión Admin", description="Operaciones exclusivas del SuperAdmin para el control de cuentas de administradores")
 */
class AdminGestion extends Controller
{
 /**
     * @OA\Get(
     * path="/api/admin/gestion",
     * summary="Listar usuarios administradores",
     * tags={"Gestión Admin"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="rows", in="query", @OA\Schema(type="integer", example=10)),
     * @OA\Response(response=200, description="Listado obtenido"),
     * @OA\Response(response=403, description="No es SuperAdmin")
     * )
     */
    public function index(Request $request)
    {
        try {
        //solo puede superAdmin
        if ($request->user()->id !== 1) {
                return response()->json([ 'message' => 'Acceso denegado.'], 403);
            }
            // Obtenemos los administradores (role_id 1)

      $perPage = $request->get('rows', 10);

            //  usar paginate() para que Angular reciba el objeto 'total', 'data', etc.
            $admins = User::where('role_id', 1)
                ->select('id', 'name', 'email', 'status', 'change_pass')
                ->paginate($perPage);

            return response()->json([
                'message' => 'Listado de administradores obtenido correctamente',
                'data'    => $admins
            ], 200);
        } catch (Exception $e) {
            return response()->json([
              
                'message' => 'Error al obtener la lista de administradores'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/admin/gestion",
     * summary="Crear nuevo administrador",
     * tags={"Gestión Admin"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * @OA\JsonContent(
     * required={"name","email"},
     * @OA\Property(property="name", type="string"),
     * @OA\Property(property="email", type="string")
     * )
     * ),
     * @OA\Response(response=201, description="Admin creado con clave temporal")
     * )
     */
    public function store(Request $request)
    {
         try {
        //dolo superAdmin
        if ($request->user()->id !== 1) {
            return response()->json(['message' => 'No tienes permisos'], 403);
        }
        // validacion
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|string|email|max:255|unique:users',
            
        ]);

       
          $passwordTemporal = Str::random(8);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($passwordTemporal),
                'role_id' => 1,
                'validado' => 1,
                'change_pass' => true, // cambio a true par acambio contraseña
                'status' => UserEstado::ACTIVO->value
            ]);

            return response()->json([
             
                'message' => 'Administrador creado. Debe cambiar su clave al entrar.',
                'data' => [
                    'pass_temporal' => $passwordTemporal, 
                   
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'No se pudo crear el administrador.',
                'errors'=>$e->getMessage()
            ], 500);
        }
    }

 /**
     * @OA\Patch(
     * path="/api/admin/gestion/{id}/reset-password",
     * summary="Resetear password de un administrador",
     * tags={"Gestión Admin"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Password reseteado"),
     * @OA\Response(response=403, description="Intento de resetear al SuperAdmin o acceso no permitido")
     * )
     */
  public function resetAdminPassword(Request $request, $id)
    {
        
        // para asegurar que solo el ID 1 puede resetear a otros admins.
        if ($request->user()->id !== 1 || (int)$id === 1) {
            return response()->json(['mensaje' => false, 'message' => 'Acceso no permitido'], 403);
        }

        try {
            $user = User::where('role_id', 1)->findOrFail($id);
            $newPass = Str::random(8);

            $user->update([
                'password' => Hash::make($newPass),
                'change_pass' => true
            ]);

            return response()->json([
              
                'message' => 'Contraseña reseteada con éxito',
                'data' => ['pass_temporal' => $newPass]
            ], 200);
        } catch (Exception $e) {
            return response()->json([ 'message' => 'Error al resetear'], 500);
        }
    }
    
  
}
