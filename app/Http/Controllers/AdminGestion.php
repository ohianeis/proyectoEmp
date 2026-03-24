<?php

namespace App\Http\Controllers;

use App\Enums\UserEstado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;

class AdminGestion extends Controller
{
  /**
     * Listar administradores
     * 
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

            // Importante: usar paginate() para que Angular reciba el objeto 'total', 'data', etc.
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
     * Crear un nuevo administrador (Solo por SuperAdmin)
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
                'change_pass' => true, // Reutilizamos tu lógica de cambio forzoso
                'status' => UserEstado::ACTIVO->value
            ]);

            return response()->json([
             
                'message' => 'Administrador creado. Debe cambiar su clave al entrar.',
                'data' => [
                    'pass_temporal' => $passwordTemporal, // Se la das al compañero
                   
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
     * Resetear password (Crea uno temporal y marca change_pass)
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
