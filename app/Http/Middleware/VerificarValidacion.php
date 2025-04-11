<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarValidacion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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
    if (!$user->validado) {
        return response()->json([
            'mensaje' => 'Tu cuenta aún no ha sido validada por parte del centro.'
        ], 422);
    }

    return $next($request);
    }
}
