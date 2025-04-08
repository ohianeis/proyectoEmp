<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\TituloController;
use App\Http\Controllers\ValidacionController;
use App\Http\Middleware\authValidacion;
use App\Http\Middleware\VerificarValidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/

/*Route::controller(AuthController::class)->middleware(authValidacion::class)->group(function(){
    Route::post('/registro', 'registro');
    Route::post('/login', 'login')->name('login');
});*/
Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/registro/roles',[AuthController::class,'roles']);

Route::middleware(['auth:sanctum'],VerificarValidacion::class)->group(function () {
    //ofertas
    Route::get('/ofertas', [OfertaController::class, 'index']);
    Route::get('ofertas/{oferta}',[OfertaController::class,'show']);
Route::post('/ofertas/{oferta}/candidatos/{demandante}/inscribir',[OfertaController::class,'añadirCandidato']);

    Route::post('/ofertas', [OfertaController::class, 'store']);
    Route::post('/ofertas/{oferta}/apuntarse',[OfertaController::class,'apuntarseOferta']);
    Route::delete('ofertas/{oferta}/desapuntarse',[OfertaController::class,'desapuntarseOferta']);
    Route::get('ofertas/inscritas',[OfertaController::class,'ofertasInscritas']);
    Route::get('ofertas/{oferta}/candidatos',[OfertaController::class,'candidatosInscritos']);
    Route::get('ofertas/{oferta}/candidatos/{demandante}',[OfertaController::class,'detalleCandidato']);
    Route::get('/ofertas/{oferta}/noInscritos',[OfertaController::class,'candidatosNoInscritos']);

    //rutas perfiles
       Route::get('/perfil',[PerfilController::class,'index'])->middleware('ability:administrador,empresa,demandante');
       Route::patch('/perfil/editar',[PerfilController::class,'update'])->middleware('ability:administrador,empresa,demandante');
    Route::post('perfil/direccion', [PerfilController::class,'store'])->middleware('ability:empresa,demandante');
    Route::patch('perfil/direccion/{direccion}', [PerfilController::class,'actualizarDireccion'])->middleware('ability:empresa,demandante');


    Route::get('/titulos/activos', [TituloController::class,'titulosActivos'])->middleware(['ability:administrador,demandante,empresa']);//pueden acceder los tres roles
    Route::post('/titulos/demandante', [TituloController::class,'agregarTitulos'])->middleware(['ability:demandante']);//pueden acceder solo los demandantes
    Route::get('/titulos/demandante',[TituloController::class,'titulosDemandante'])->middleware(['ability:demandante']);
    Route::delete('/titulos/demandante/{id}',[TituloController::class,'tituloDemandante'])->middleware(['ability:demandante']);

    //grupo rutas titulos solo accesible por administrador (el centro)
    Route::controller(TituloController::class)->middleware(['ability:administrador'])->group(function () {
        Route::get('/titulos', 'index');
        Route::get('/titulos/{titulo}', 'show');
        Route::get('/titulos/niveles/listado','nivel');
     
        Route::patch('/titulos/{titulo}', 'update');
        Route::post('/titulos', 'store');
        Route::delete('/titulos/{titulo}', 'destroy');
    });
    //rutas para las validaciones solo accesible por el centro
    Route::controller(ValidacionController::class)->middleware('ability:administrador')->group(function(){
       Route::get('/usuarios/validaciones','index');
       Route::patch('/usuarios/validaciones/{user}','update');
       Route::delete('/usuarios/validaciones/{user}','destroy');
    });

});
//controlador titulos



/*Route::middleware(['auth:sanctum'])->group(function () {
 
});
Route::middleware(['auth:sanctum'])->get('/titulos',[TituloController::class,'index']);*/
 //  Route::get('/titulos/activos', [TituloController::class, 'titulosActivos']);