<?php

namespace App\Http\Controllers;

use App\Models\DetalleMotivo;
use App\Models\Motivo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DetalleMotivoController extends Controller
{
    /**
     * Lista todos los motivos y sus detalles (Para el Panel de Admin)
     */
    public function index()
    {
        try {

            // Admin querrá ver la jerarquía completa para gestionar

            $usuario = Auth::user();

            // Control de acceso: Solo el administrador (Rol 1) debería ver TODO
            if ($usuario->role_id != 1) {
                return response()->json([

                    'message' => 'No tienes permisos para gestionar motivos'
                ], 403);
            }

            $datos = Motivo::with(['detalles'])
                ->where('id', 2)
                ->get();
            return response()->json([

                'message' => 'Motivos de cierre oferta cargados con éxito',
                'data'    => $datos
            ], 200);
        } catch (Exception $e) {
            return response()->json([

                'message' => 'Error al obtener la lista de motivos',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista detalles filtrados por motivo y que estén activos (Para la Empresa al cerrar oferta)
     */
    public function listarActivosPorMotivo()
    {
        try {
            $usuario = Auth::user();

            // Controla que el usuario esté autenticado y sea Empresa 
            if (!$usuario || $usuario->role_id !== 2) {
                return response()->json([
                    'message' => 'No tienes permisos para ver estos motivos de cierre'
                ], 403);
            }
            $detalles = DetalleMotivo::where('motivo_id', 2)
                ->where('activo', true)
                ->get();

            return response()->json([

                'message' => 'Detalles de cierre recuperados con éxito',
                'data'    => $detalles
            ], 200);
        } catch (Exception $e) {
            return response()->json([

                'message' => 'Error al filtrar detalles',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo detalle de motivo (Solo Admin)
     */
    public function store(Request $request)
    {

        try {

            $usuario = Auth::user();

            // Verificamos que el usuario esté autenticado y es Admin (Rol 1)
            if (!$usuario || $usuario->role_id !== 1) {
                return response()->json([
                    'message' => 'No tienes permisos de administrador para crear motivos.'
                ], 403);
            }
            $validacion = $request->validate([
                'nombre'    => 'required|string|max:255',
                'motivo_id' => 'required|exists:motivos,id',
            ]);

            $detalle = DetalleMotivo::create([
                'nombre'    => $validacion['nombre'],
                'motivo_id' => $validacion['motivo_id'],
                'activo'    => true
            ]);

            return response()->json([
                'message' => 'Detalle de motivo creado correctamente',
                'data'    => $detalle
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'No se ha podido crear el nuevo motivo',
                'errors' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el detalle',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar detalle (Nombre o Estado activo/inactivo)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role_id !== 1) {
                return response()->json([
                    'message' => 'Acceso denegado. Se requieren permisos de administrador',
                ], 403);
            }
            if ($id == 1) {
                return response()->json([
                    'message' => 'Este motivo no puede ser modificado.'
                ], 403);
            }
            $detalle = DetalleMotivo::findOrFail($id);

            $validacion = $request->validate([

                'nombre' => 'sometimes|required|string|max:255|unique:detalle_motivos,nombre,' . $id,
                'activo' => 'sometimes|required|boolean',
                'motivo_id' => 'sometimes|required|exists:motivos,id'
            ]);
            $detalle->update($validacion);

            return response()->json([
                'message' => 'Detalle actualizado correctamente',
                'data'    => $detalle
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'No se pudo actualizar el detalle',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }
}
