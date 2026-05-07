<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HideSwaggerInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        // Usamos config() porque es 100% resistente a las cachés del servidor
        if (config('app.env') === 'production') {
            abort(404);
        }

        return $next($request);
    }
}