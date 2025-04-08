<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class authValidacion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //Obtener api por parte js
        $clave=$request->header('API-KEY');
        //ver si coincide con mi api en archivo .env
        if(!$clave|| $clave !== config('app.api_key')){
            return response()->json([
                'mensaje'=>'Acceso no autorizado'
            ],402);
        }
        return $next($request);
    }
}
