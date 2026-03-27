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
use App\Http\Controllers\CvController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/registro/roles', [AuthController::class, 'roles']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (AUTENTICACIÓN Y VALIDACIÓN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', \App\Http\Middleware\VerificarValidacion::class])->group(function () {

    // Gestionar sesión y credenciales básicas
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/perfil-auth', [AuthController::class, 'perfil']);
    Route::post('/change-password-user', [BajaController::class, 'changePassUser']);

    // Rutas que requieren haber superado el cambio de contraseña obligatorio
    Route::middleware([\App\Http\Middleware\changePass::class])->group(function () {

        /* --- ACCESO EMPRESA --- */
        Route::middleware(['ability:empresa'])->group(function () {
            Route::get('/empresa/stats', [StatsEmpresaController::class, 'getStatsEmpresa']);
            
            Route::controller(OfertaController::class)->group(function () {
                Route::get('ofertas/estados-candidatos', 'getEstadosCandidato');
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
                Route::patch('ofertas/{id}/toggle-anonimo', 'cambiarAnonimato');
            });
        });

        /* --- CONFIGURACIÓN DE MOTIVOS DE CIERRE --- */
        Route::prefix('configuracion-cierre')->controller(\App\Http\Controllers\DetalleMotivoController::class)->group(function () {
            // Listar detalles activos para el selector de cierre (Empresa)
            Route::get('/detalles/activos', 'listarActivosPorMotivo')->middleware('ability:empresa');

            // Gestionar configuración de motivos (Administrador)
            Route::middleware(['ability:administrador'])->group(function () {
                Route::get('/motivos-admin', 'index');
                Route::post('/detalles', 'store');
                Route::patch('/detalles/{id}', 'update');
            });
        });

        /* --- ACCESO MULTIPERFIL (EMPRESA Y DEMANDANTE) --- */
        Route::controller(OfertaController::class)->middleware(['ability:empresa,demandante'])->group(function () {
            Route::get('/ofertas', 'index');
            Route::get('ofertas/{oferta}', 'show');
        });

        /* --- ACCESO DEMANDANTE --- */
        Route::middleware(['ability:demandante'])->group(function () {
            Route::controller(OfertaController::class)->group(function () {
                Route::post('/ofertas/{oferta}/apuntarse', 'apuntarseOferta');
                Route::delete('ofertas/{oferta}/desapuntarse', 'desapuntarseOferta');
                Route::get('ofertas/inscritas/listado', 'ofertasInscritas');
            });
            Route::get('/demandante/stats-dashboard', [StatsAlumnoController::class, 'getDashboardStats']);
        });

        /* --- GESTIÓN DE PERFIL Y TÍTULOS --- */
        Route::get('/perfil', [PerfilController::class, 'index'])->middleware('ability:empresa,demandante');
        Route::patch('/perfil/editar', [PerfilController::class, 'update'])->middleware('ability:empresa,demandante');
        Route::post('perfil/direccion', [PerfilController::class, 'store'])->middleware('ability:empresa,demandante');
        Route::get('/perfil/situaciones', [PerfilController::class, 'listarSituaciones'])->middleware('ability:demandante');

        Route::get('/titulos/familias', [TituloController::class, 'familias'])->middleware(['ability:administrador,demandante,empresa']);
        Route::get('/titulos/activos', [TituloController::class, 'titulosActivos'])->middleware(['ability:administrador,demandante,empresa']);
        
        Route::controller(TituloController::class)->middleware(['ability:demandante'])->group(function () {
            Route::post('/titulos/demandante', 'agregarTitulos');
            Route::get('/titulos/demandante', 'titulosDemandante');
            Route::delete('/titulos/demandante/{id}', 'tituloDemandante');
        });

        /* --- ACCESO ADMINISTRADOR (CENTRO) --- */
        Route::middleware(['ability:administrador'])->group(function () {
            
            // Administrar catálogo de títulos y familias
            Route::controller(TituloController::class)->group(function () {
                Route::get('/titulos', 'index');
                Route::get('/titulos/{titulo}', 'show');
                Route::get('/titulos/niveles/listado', 'nivel');
                Route::post('titulos/familias', 'storeFamilia');
                Route::patch('titulos/familias/{id}', 'updateFamilia');
                Route::delete('titulos/familias/{id}', 'destroyFamilia');
                Route::patch('/titulos/{titulo}', 'update');
                Route::post('/titulos', 'store');
                Route::delete('/titulos/{titulo}', 'destroy');
            });

            // Gestionar validaciones de usuarios
            Route::controller(ValidacionController::class)->group(function () {
                Route::get('/usuarios/validaciones', 'index');
                Route::get('/usuarios/validaciones/pendientes', 'getPendientesCount');
                Route::patch('/usuarios/validaciones/{user}', 'update');
                Route::delete('/usuarios/validaciones/{user}', 'destroy');
            });

            // Generar informes y estadísticas
            Route::controller(InformeController::class)->group(function () {
                Route::get('/informes/ofertasAsignadas', 'ofertasAsignadas');
                Route::get('informes/detalleOfertasAsignadas', 'detalleOfertasAsignadas');
                Route::get('informes/ofertasCerradas', 'ofertasCerradas');
                Route::get('informes/ofertasAbiertas', 'ofertasAbiertas');
                Route::get('informes/totalDemandantes', 'totalDemandantes');
                Route::get('informes/totalEmpresas', 'totalEmpresas');
                Route::get('informes/titulosEstado', 'titulosEstado');
                Route::get('informes/empresasSinOfertas', 'empresasSinOfertas');
                Route::get('informes/ofertasSinPostulantes', 'ofertasSinPostulantes');
                Route::get('/informes/empresa/{id}', 'detalleEmpresaAdmin');
                Route::get('informes/oferta/{id}', 'detalleOfertaAdmin');
                Route::get('informes/all-alumnos', 'getAllAlumnos');
                Route::get('informes/all-empresas', 'getAllEmpresas');
                Route::get('informes/alumno/{id}', 'getDetalleAlumnoAdmin');
                Route::get('informes/reportes/{tipo}', 'getReportesEspeciales');
            });

            // Gestionar personal de administración
            Route::controller(\App\Http\Controllers\AdminGestion::class)->prefix('admin-staff')->group(function () {
                Route::get('/listado', 'index');
                Route::post('/crear', 'store');
                Route::post('/reset-password/{id}', 'resetAdminPassword');
            });
        });

        /* --- GESTIÓN DE BAJAS --- */
        Route::prefix('bajas')->controller(BajaController::class)->group(function () {
            Route::get('/motivos', 'listarMotivos');
            
            Route::middleware(['ability:empresa,demandante'])->group(function () {
                Route::post('/ejecutar', 'ejecutarBaja');
            });

            Route::middleware(['ability:administrador'])->group(function () {
                Route::get('/historial', 'indexHistorialBajas');
                Route::post('/motivos', 'storeMotivo');
                Route::put('/motivos/{id}', 'updateMotivo');
                Route::delete('/motivos/{id}', 'destroyMotivo');
                Route::post('/admin/baja-forzosa/{idUsuario}', 'bajaPorAdmin');
                Route::patch('/reactivar/{idUsuario}', 'reactivarUsuario');
                Route::post('/admin/reset-password/{idUsuario}', 'changePassAdmin');
            });
        });

        /* --- GESTIÓN DE CURRÍCULUM (CV) --- */
        Route::controller(CvController::class)->group(function () {
            Route::middleware(['ability:demandante'])->group(function () {
                Route::get('/cv', 'show');
                Route::post('/cv', 'upload');
                Route::delete('/cv', 'destroy');
            });
            Route::get('/cv/empresa/{oferta_id}/{demandante_id}', 'showEmpresa')->middleware('ability:empresa');
        });
    });
});