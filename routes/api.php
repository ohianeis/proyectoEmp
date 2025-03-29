<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\TituloController;
use App\Http\Controllers\ValidacionController;
use App\Http\Middleware\VerificarValidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/

Route::get('ofertas', [OfertaController::class, 'index']);

Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/titulos/activos', [TituloController::class,'titulosActivos'])->middleware(['ability:administrador,demandante,empresa']);//pueden acceder los tres roles
    Route::post('/titulos/demandante', [TituloController::class,'agregarTitulos'])->middleware(['ability:demandante']);//pueden acceder solo los demandantes
    Route::get('titulos/demandante',[TituloController::class,'titulosDemandante'])->middleware(['ability:demandante']);
    Route::delete('titulos/demandante/{id}',[TituloController::class,'tituloDemandante'])->middleware(['ability:demandante']);

    //grupo rutas titulos solo accesible por administrador (el centro)
    Route::controller(TituloController::class)->middleware(['ability:administrador'])->group(function () {
        Route::get('/titulos', 'index');
        Route::get('/titulos/{titulo}', 'show');
     
        Route::patch('/titulos/{titulo}', 'update');
        Route::post('/titulos', 'store');
        Route::delete('/titulos/{titulo}', 'destroy');
    });

});
//controlador titulos



/*Route::middleware(['auth:sanctum'])->group(function () {
 
});
Route::middleware(['auth:sanctum'])->get('/titulos',[TituloController::class,'index']);*/
 //  Route::get('/titulos/activos', [TituloController::class, 'titulosActivos']);