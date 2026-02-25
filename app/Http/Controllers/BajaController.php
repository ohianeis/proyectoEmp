<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Enums\ResultadoBaja;
use App\Enums\UserEstado;
use App\Models\MotivoBaja;
use App\Models\SolicitudBaja;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BajaController extends Controller
{
    //motivos de baja
    public function index()
    {
        try {
            //solicitudes baja no tramitadas manda conteo por si un usuario manda muchas veces
         $solicitudes = SolicitudBaja::with(['user', 'motivoBaja'])
            ->withCount(['user as intentos_totales' => function($query) {
                // Esto contará todas las solicitudes de ese usuario en la historia
                $query->select(DB::raw('count(*)'));
            }])
            ->where('tramitada', false)
            ->orderBy('created_at', 'asc') // Las más antiguas primero para no hacer esperar
            ->paginate(10);

            return response()->json([
                'data' => $solicitudes,
                'message' => 'Solicitudes de baja recuperadas correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Hubo un error al consultar las solicitudes', 'errors' => $e->getMessage()], 500);
        }
    }
    public function listarMotivos()
    {

        try {
            $msgExito = 'Datos obtenidos correctamente';
            $user = Auth::user();
            if ($user->role_id == 1) {
                return response()->json([
                    'data' => MotivoBaja::all(),
                    'message' => $msgExito
                ], 200);
            }
            if ($user->role_id == 2) {
                return response()->json([
                    'data' => MotivoBaja::where('visibleEmpresa', true)->get(),
                    'message' => $msgExito
                ], 200);
            }
            if ($user->role_id == 3) {
                return response()->json([
                    'data' => MotivoBaja::where('visibleAlumno', true)->get(),
                    'message' => $msgExito
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //crear una solicitud de baja por parte de un usuario (alumno/empresa)
    public function solicitarBaja(Request $request)
    {
        try {
            $user = Auth::user();
            // Comprobamos si ya tiene una solicitud sin tramitar
            $existePendiente = SolicitudBaja::where('user_id', $user->id)
                ->where('tramitada', false)
                ->exists();

            if ($existePendiente) {
                return response()->json([
                    'message' => 'Ya tienes una solicitud de baja en proceso. Espera a que el administrador la revise.'
                ], 422);
            }
            $request->validate([
                'motivo_baja_id' => 'required|exist:motivos_bajas,id',
                'comentario' => 'nullabe|string|max:500'
            ]);
            /** @var \App\Models\User $user */
            $user = Auth::user();
            //creo entrada en tabla Solicitud baja
            SolicitudBaja::create([
                'user_id' => $user->id,
                'motivo_baja_id' => $request->motivo_baja_id,
                'comentario' => $request->comentario,
                'tramitada' => false
            ]);
            //cambio status usuario
            $user->status = UserEstado::PENDIENTE_BAJA;
            $user->save();

            return response()->json(['message' => 'Solicitud de baja enviada correctamente'], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al procesar la solicitud',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    public function confirmarBaja($idSolicitud, Request $request)
    {
        try {
            $solicitud = SolicitudBaja::findOrFail($idSolicitud);
            $user = $solicitud->user;
            $request->validate([
                'nota_admin' => 'nullable|string|max:1000'
            ]);
            //  solicitud como tramitada
            $solicitud->update([
                'tramitada' => true,
                'resultado' => ResultadoBaja::ACEPTADA->value,
                'notaAdmin' => $request->nota_admin // Opcional
            ]);

            // Cambiar status del usuario a inactivo 
            $user->status = UserEstado::INACTIVO;
            $user->save();


            return response()->json(['message' => 'Baja confirmada y usuario desactivado']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al confirmar', 'errors' => $e->getMessage()], 500);
        }
    }
    public function rechazarBaja(Request $request, $idSolicitud)
    {
        try {
            $solicitud = SolicitudBaja::findOrFail($idSolicitud);
            $user = $solicitud->user;
            $request->validate([
                'notaAdmin' => 'required|string|min:10|max:1000'

            ]);
            // Actualizamos  para mantener historial
            $solicitud->update([
                'tramitada' => true,
                'resultado' => ResultadoBaja::RECHAZADA->value,
                'notaAdmin' => $request->notaAdmin
            ]);

            //poner al usuario a su estado anterior

            $user->status = UserEstado::ACTIVO;
            $user->save();

            return response()->json(['message' => 'Solicitud de baja rechazada, el usuario vuelve a estar activo']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al rechazar', 'errors' => $e->getMessage()], 500);
        }
    }
}
