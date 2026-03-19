<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class changePass
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Si el usuario está autenticado y tiene marcado el cambio forzoso
        if ($user && $user->force_password_change) {
            
         // Rutas EXENTAS del bloqueo:
            // 1. logout: para que pueda salir si no quiere cambiarla ahora.
            // 2. perfil-auth: para que Angular cargue el usuario inicial.
            // 3. change-password-user: la ruta para guardar la nueva clave.
            if ($request->is('api/logout') || 
                $request->is('api/perfil-auth') || 
                $request->is('api/change-password-user')) {
                return $next($request);
            }
            // Para cualquier otra ruta, bloqueamos con un 403
            return response()->json([
                'message' => 'FORCE_PASSWORD_CHANGE', // Código que leerá Angular
                'data' => [
                    'nombre' => $user->name,
                    'email' => $user->email
                ]
            ], 403);
        }

        return $next($request);
    }
}
