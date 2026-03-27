<?php

namespace App\Http\Middleware;

use App\Enums\UserEstado;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarValidacion
{
   /**
     * Verificar el estado de activación y validación del usuario.
     * * Comprobar la autenticación del usuario, validar que su estado sea 'Activo'
     * y asegurar que perfiles de empresa o alumno hayan sido aprobados por el centro
     * para permitir el acceso a las funciones del sistema.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
    if (!Auth::check()) {
        return response()->json([
            'mensaje' => 'No estás autenticado. Por favor, inicia sesión.'
        ], 401);
    }

    $user = Auth::user();

    // Verificar si el usuario ha sido validado por el centro
    $statusActual = ($user->status instanceof \BackedEnum) 
            ? $user->status->value 
            : $user->status;
    // ver si esta activo (Para todos: Alumnos, Empresas y Admins)
        // Si el Admin se va del trabajo y se pone como inactivo, no podrá entrar.
        if ($statusActual !== UserEstado::ACTIVO->value) {
            return response()->json([
                'mensaje' => 'Tu cuenta está inactiva. Contacta con el centro si deseas reactivarla.'
            ], 403);
        }
 
   // ver validacion de empresa y alumno
        // El Admin (role_id == 1) no necesita ser validado por nadie.
        if ((int)$user->role_id !== 1 && !(bool)$user->validado) {
            return response()->json([
                'mensaje' => 'Tu cuenta aún no ha sido validada por parte del centro.'
            ], 422);
        }
    return $next($request);
    }
}
