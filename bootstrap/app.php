<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Middleware\HandleCors;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //aplicar cors para comunicacion con fronted
   /*     $middleware->api(append: [
            HandleCors::class,
        ]);*/
        //
        $middleware->alias([
            'abilities'=>CheckAbilities::class,
            'ability'=>CheckForAnyAbility::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
       $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
           

            // Para otras rutas no encontradas
            return response()->json([
                'error' => 'Recurso no encontrado'
            ], 404);
          
       
        });
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'error' => 'Acceso denegado. No tienes permisos para realizar esta acción.'
            ], 403);
        });
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'error' => 'No estás autenticado. Por favor, inicia sesión para continuar.'
            ], 401);
        });
        
    })->create();
