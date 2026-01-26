<?php

use App\Http\Controllers\AuthController;

use App\Http\Controllers\InformeController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\StatsEmpresaController;
use App\Http\Controllers\TituloController;
use App\Http\Controllers\ValidacionController;
use App\Http\Middleware\authValidacion;
use App\Http\Middleware\VerificarValidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/registro/roles',[AuthController::class,'roles']);

Route::middleware(['auth:sanctum', \App\Http\Middleware\VerificarValidacion::class])->group(function () {
 Route::middleware(['ability:empresa'])->group(function () {
Route::get('/empresa/stats', [StatsEmpresaController::class, 'getStatsEmpresa'])->middleware('ability:empresa');    });
   Route::controller(OfertaController::class)->middleware(['ability:empresa'])->group(function () {
        Route::get('ofertas/estados-candidatos', [OfertaController::class, 'getEstadosCandidato']);

        Route::post('/ofertas', 'store');
        Route::get('ofertas/{oferta}/candidatos','candidatosInscritos');
        Route::get('ofertas/{oferta}/candidatos/{demandante}','detalleCandidato');
        Route::get('/ofertas/{oferta}/noInscritos','candidatosNoInscritos');
        Route::post('/ofertas/{oferta}/candidatos/{demandante}/inscribir','añadirCandidato');
        Route::patch('ofertas/{oferta}/cerrar','cerrarOferta');
        Route::patch('ofertas/{oferta}/asignar/{demandante}','asignarCandidato');
        Route::patch('/ofertas/{oferta}/candidatos/{demandante}/seguimiento', 'actualizarSeguimiento');
    });
    //rutas ofertas accesible por empresa y demandate
    Route::controller(OfertaController::class)->middleware(['ability:empresa,demandante'])->group(function () {
        Route::get('/ofertas', 'index');
        Route::get('ofertas/{oferta}','show');
    });
 
    //rutas accesibles por demandante
    Route::controller(OfertaController::class)->middleware(['ability:demandante'])->group(function () {
        Route::post('/ofertas/{oferta}/apuntarse','apuntarseOferta');
        Route::delete('ofertas/{oferta}/desapuntarse','desapuntarseOferta');
        Route::get('ofertas/inscritas/listado','ofertasInscritas');

    });
   
 

    //rutas perfiles
       Route::get('/perfil',[PerfilController::class,'index'])->middleware('ability:empresa,demandante');
       Route::patch('/perfil/editar',[PerfilController::class,'update'])->middleware('ability:empresa,demandante');
    Route::post('perfil/direccion', [PerfilController::class,'store'])->middleware('ability:empresa,demandante');
    Route::get('/perfil/situaciones',[PerfilController::class,'listarSituaciones'])->middleware('ability:demandante');



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
    //rutas para informes accesible por el centro
    Route::controller(InformeController::class)->middleware('ability:administrador')->group(function(){
        Route::get('/informes/ofertasAsignadas','ofertasAsignadas');
        Route::get('informes/detalleOfertasAsignadas','detalleOfertasAsignadas');
        Route::get('informes/ofertasCerradas','ofertasCerradas');
        Route::get('informes/ofertasAbiertas','ofertasAbiertas');
        Route::get('informes/detalleOfertasAsignadas','detallesOfertasAsignadas');
        Route::get('informes/totalDemandantes','totalDemandantes');
        Route::get('informes/totalEmpresas','totalEmpresas');
        Route::get('informes/titulosEstado','titulosEstado');
        Route::get('informes/empresasSinOfertas','empresasSinOfertas');
        Route::get('informes/ofertasSinPostulantes','ofertasSinPostulantes');






     });

});
