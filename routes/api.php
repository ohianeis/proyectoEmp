<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BajaController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\StatsAlumnoController;
use App\Http\Controllers\StatsEmpresaController;
use App\Http\Controllers\TituloController;
use App\Http\Controllers\ValidacionController;
use App\Http\Middleware\authValidacion;
use App\Http\Middleware\VerificarValidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CvController;



Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/registro/roles', [AuthController::class, 'roles']);

Route::middleware(['auth:sanctum', \App\Http\Middleware\VerificarValidacion::class])->group(function () {
    //logout
    Route::post('/logout', [AuthController::class, 'logout']);
    //compoprbacion rol usuario
    Route::get('/perfil-auth', [AuthController::class, 'perfil']);
    Route::post('/change-password-user', [BajaController::class, 'changePassUser']);

    Route::middleware([\App\Http\Middleware\changePass::class])->group(function () {
        Route::middleware(['ability:empresa'])->group(function () {
            Route::get('/empresa/stats', [StatsEmpresaController::class, 'getStatsEmpresa'])->middleware('ability:empresa');
        });
        Route::controller(OfertaController::class)->middleware(['ability:empresa'])->group(function () {
            Route::get('ofertas/estados-candidatos', [OfertaController::class, 'getEstadosCandidato']);

            Route::post('/ofertas', 'store');
            Route::get('/ofertas/{id}/edit', 'edit');
            Route::put('/ofertas/{id}', 'update');
            Route::get('ofertas/{oferta}/candidatos', 'candidatosInscritos');
            Route::get('ofertas/{oferta}/candidatos/{demandante}', 'detalleCandidato');
            Route::get('/ofertas/{oferta}/noInscritos', 'candidatosNoInscritos');
            Route::post('/ofertas/{oferta}/candidatos/{demandante}/inscribir', 'añadirCandidato');
            Route::patch('ofertas/{oferta}/cerrar', 'cerrarOferta');
            Route::patch('ofertas/{oferta}/asignar/{demandante}', 'asignarCandidato');
            Route::patch('/ofertas/{oferta}/candidatos/{demandante}/seguimiento', 'actualizarSeguimiento');
            Route::patch('ofertas/{id}/toggle-anonimo', [OfertaController::class, 'cambiarAnonimato']);
        });
        // --- RUTAS DE GESTIÓN DE MOTIVOS DE CIERRE (OFERTAS) ---
        Route::prefix('configuracion-cierre')->controller(\App\Http\Controllers\DetalleMotivoController::class)->group(function () {

            //  SOLO EMPRESA: Listar detalles activos para el selector de cierre de oferta

            Route::get('/detalles/activos', 'listarActivosPorMotivo')
                ->middleware('ability:empresa');

            //  SOLO ADMINISTRADOR: CRUD y Gestión de la configuración
            Route::middleware(['ability:administrador'])->group(function () {
                Route::get('/motivos-admin', 'index');           // Ver árbol completo (Activos e Inactivos)
                Route::post('/detalles', 'store');               // Crear nuevo detalle (ej: "Puesto cancelado")
                Route::patch('/detalles/{id}', 'update');        // Editar nombre o activar/desactivar
            });
        });
        //rutas ofertas accesible por empresa y demandate
        Route::controller(OfertaController::class)->middleware(['ability:empresa,demandante'])->group(function () {
            Route::get('/ofertas', 'index');
            Route::get('ofertas/{oferta}', 'show');
        });

        //rutas accesibles por demandante
        Route::controller(OfertaController::class)->middleware(['ability:demandante'])->group(function () {
            Route::post('/ofertas/{oferta}/apuntarse', 'apuntarseOferta');
            Route::delete('ofertas/{oferta}/desapuntarse', 'desapuntarseOferta');
            Route::get('ofertas/inscritas/listado', 'ofertasInscritas');
        });
        //datos dashboard alumno
        Route::get('/demandante/stats-dashboard', [\App\Http\Controllers\StatsAlumnoController::class, 'getDashboardStats'])
            ->middleware('ability:demandante');

        //rutas perfiles
        Route::get('/perfil', [PerfilController::class, 'index'])->middleware('ability:empresa,demandante');
        Route::patch('/perfil/editar', [PerfilController::class, 'update'])->middleware('ability:empresa,demandante');
        Route::post('perfil/direccion', [PerfilController::class, 'store'])->middleware('ability:empresa,demandante');
        Route::get('/perfil/situaciones', [PerfilController::class, 'listarSituaciones'])->middleware('ability:demandante');


        Route::get('/titulos/familias', [TituloController::class, 'familias'])->middleware(['ability:administrador,demandante,empresa']); //pueden acceder los tres roles

        Route::get('/titulos/activos', [TituloController::class, 'titulosActivos'])->middleware(['ability:administrador,demandante,empresa']); //pueden acceder los tres roles
        Route::post('/titulos/demandante', [TituloController::class, 'agregarTitulos'])->middleware(['ability:demandante']); //pueden acceder solo los demandantes
        Route::get('/titulos/demandante', [TituloController::class, 'titulosDemandante'])->middleware(['ability:demandante']);
        Route::delete('/titulos/demandante/{id}', [TituloController::class, 'tituloDemandante'])->middleware(['ability:demandante']);


        //grupo rutas titulos solo accesible por administrador (el centro)
        Route::controller(TituloController::class)->middleware(['ability:administrador'])->group(function () {
            Route::get('/titulos', 'index');
            Route::get('/titulos/{titulo}', 'show');
            Route::get('/titulos/niveles/listado', 'nivel');
            //para administrar familias

            Route::post('titulos/familias', 'storeFamilia');
            Route::patch('titulos/familias/{id}', 'updateFamilia');
            Route::delete('titulos/familias/{id}', 'destroyFamilia');
            //administrar titulos
            Route::patch('/titulos/{titulo}', 'update');
            Route::post('/titulos', 'store');
            Route::delete('/titulos/{titulo}', 'destroy');
        });

        //rutas para las validaciones solo accesible por el centro
        Route::controller(ValidacionController::class)->middleware('ability:administrador')->group(function () {
            Route::get('/usuarios/validaciones', 'index');
            Route::get('/usuarios/validaciones/pendientes', 'getPendientesCount');
            Route::patch('/usuarios/validaciones/{user}', 'update');
            Route::delete('/usuarios/validaciones/{user}', 'destroy');
        });
        //rutas para informes accesible por el centro
        Route::controller(InformeController::class)->middleware('ability:administrador')->group(function () {
            Route::get('/informes/ofertasAsignadas', 'ofertasAsignadas');
            Route::get('informes/detalleOfertasAsignadas', 'detalleOfertasAsignadas');
            Route::get('informes/ofertasCerradas', 'ofertasCerradas');
            Route::get('informes/ofertasAbiertas', 'ofertasAbiertas');
            Route::get('informes/detalleOfertasAsignadas', 'detallesOfertasAsignadas');
            Route::get('informes/totalDemandantes', 'totalDemandantes');
            Route::get('informes/totalEmpresas', 'totalEmpresas');
            Route::get('informes/titulosEstado', 'titulosEstado');
            Route::get('informes/empresasSinOfertas', 'empresasSinOfertas');
            Route::get('informes/ofertasSinPostulantes', 'ofertasSinPostulantes');
            Route::get('/informes/empresa/{id}', 'detalleEmpresaAdmin');
            Route::get('informes/oferta/{id}', 'detalleOfertaAdmin');
            Route::get('informes/all-alumnos', 'getAllAlumnos');
            Route::get('informes/all-empresas', 'getAllEmpresas');

            Route::get('informes/alumno/{id}', [InformeController::class, 'getDetalleAlumnoAdmin']);
            Route::get('informes/reportes/{tipo}', [InformeController::class, 'getReportesEspeciales']);
        });

        // --- RUTAS DE GESTIÓN DE BAJAS ---

        // Rutas accesibles por todos (Empresa, Demandante y Administrador)
        Route::prefix('bajas')->controller(BajaController::class)->group(function () {

            //  Ruta común para TODOS (Admin, Empresa, Demandante)
            // El controlador filtra qué motivos ve cada uno internamente
            Route::get('/motivos', 'listarMotivos');

            //Rutas exclusivas para Usuarios (Empresa/Demandante)
            Route::middleware(['ability:empresa,demandante'])->group(function () {
                Route::post('/ejecutar', 'ejecutarBaja');
            });

            //  Rutas exclusivas para el Administrador
            Route::middleware(['ability:administrador'])->group(function () {
                Route::get('/historial', 'indexHistorialBajas');
                Route::post('/motivos', 'storeMotivo');
                Route::put('/motivos/{id}', 'updateMotivo');
                Route::delete('/motivos/{id}', 'destroyMotivo');

                // baja forzosa por admin
                Route::post('/admin/baja-forzosa/{idUsuario}', 'bajaPorAdmin');
                //reactivar baja
                Route::patch('/reactivar/{idUsuario}', [BajaController::class, 'reactivarUsuario']);
            Route::post('/admin/reset-password/{idUsuario}', [BajaController::class, 'changePassAdmin']);
           
                });
        });
        //--RUTAS PARA GESTION CV---
        //PARA DEMANDANTE
        Route::controller(CvController::class)->middleware(['ability:demandante'])->group(function () {
            Route::get('/cv', 'show');          // Ver datos de su propio CV
            Route::post('/cv', 'upload');       // Subir o reemplazar CV
            Route::delete('/cv', 'destroy');    // Eliminar su CV
        });
        //PARA EMPRESA
        Route::get('/cv/empresa/{oferta_id}/{demandante_id}', [CvController::class, 'showEmpresa'])
            ->middleware('ability:empresa');
    });
});
