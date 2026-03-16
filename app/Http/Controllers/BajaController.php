<?php

namespace App\Http\Controllers;

use App\Enums\UserEstado;
use App\Models\MotivoBaja;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BajaController extends Controller
{
    /**
     * Lista los motivos de baja según el rol del usuario autenticado.
     */
    public function listarMotivos()
    {
        try {
            $user = Auth::user();
            $query = MotivoBaja::query();

            // Filtrar según el rol (Admin ve todos, Empresa/Alumno ven los suyos)
            if ($user->role_id == 2) { // Empresa
                $query->where('visible_empresa', true);
            } elseif ($user->role_id == 3) { // Alumno
                $query->where('visible_alumno', true);
            }

            return response()->json([
                'data' => $query->get(),
                'message' => 'Motivos recuperados correctamente'
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener motivos', 'errors' => $e->getMessage()], 500);
        }
    }

    /**
     * Ejecuta la baja inmediata del usuario si cumple los requisitos.
     */
    public function ejecutarBaja(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            //  Validar datos
            $request->validate([
                'motivo_baja_id' => 'required|exists:motivo_bajas,id',
                'comentario' => 'nullable|string|max:500'
            ]);

            
            
            // empresa no puede tener ofertas abiertas
            if ($user->role_id == 2) {
                $tieneOfertasActivas = $user->empresa->ofertas()
                    ->where('estado_id', 1) 
                    ->exists();

                if ($tieneOfertasActivas) {
                    return response()->json([
                        'message' => 'No puedes darte de baja. Tienes ofertas publicadas actualmente. Debes cerrarlas primero.'
                    ], 422);
                }
            }

            // alumno no puede si esta inscrito en alguna oferta, controla si esta ha conseguido el puesto pero oferta sigue abierta x si esta no termino
  if ($user->role_id == 3) { 
    // Comprobamos si tiene inscripciones en ofertas NO cerradas
    // PERO solo contamos aquellas donde NO esté ya "Retirado" (ID 8)
    $tieneProcesosActivos = $user->demandante->ofertas()
        ->where('ofertas.estado_id', 1) // La oferta esta abierta
        ->wherePivot('estado_candidato_id', '!=', 8) // ¡CLAVE! Que no esté ya retirado
        ->exists();

    if ($tieneProcesosActivos) {
        return response()->json([
            'message' => 'No puedes darte de baja. Tienes candidaturas activas en ofertas abiertas. Debes retirarlas primero.'
        ], 422);
    
    }
}


            // baja en portal, soft deletes no se borra del todo pasa a inactivo
            DB::transaction(function () use ($user, $request) {
                $user->update([
                    'status' => UserEstado::INACTIVO->value,
                    'motivo_baja_id' => $request->motivo_baja_id,
                    'comentario_baja' => $request->comentario,
                    'fecha_baja' => now(),
                    'validado' => false // doy a false por si vuelve admin tenga que validarlo otra vez
                ]);

                // Revocar sus tokens de acceso (Cerrar sesión en todos los dispositivos)
                $user->tokens()->delete();
            });

            return response()->json([
                'message' => 'Tu cuenta ha sido desactivada correctamente. Lamentamos que te vayas.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Hubo un error al procesar la baja',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista para el ADMIN: Ver quién se ha dado de baja y por qué.
     */
    public function indexHistorialBajas(Request $request)
    {
       try {
        $busqueda=$request->query('busqueda');
        $rows = $request->query('rows', 10);
        $query = User::with(['rol:id,rol', 'motivoBaja:id,motivo', 'demandante:id,user_id', 'empresa:id,user_id,cif'])
            ->where('status', UserEstado::INACTIVO->value)
            ->whereNotNull('fecha_baja');

        // 3. APLICAMOS EL FILTRO (Si existe búsqueda)
        if (!empty($busqueda)) {
            $query->where(function($q) use ($busqueda) {
                $q->where('name', 'LIKE', "%{$busqueda}%")
                  ->orWhere('email', 'LIKE', "%{$busqueda}%");
            });
        }
        
       $bajas = $query->select('id', 'name', 'email', 'role_id', 'motivo_baja_id', 'comentario_baja', 'fecha_baja')
            ->orderBy('fecha_baja', 'desc')
            ->paginate($rows); 

        // Transformamos para aplanar la respuesta y que sea fácil de leer en Angular
        $bajas->getCollection()->transform(function ($user) {
            return [
                'id'              => $user->id,
                'nombre'          => $user->name,
                'email'           => $user->email,
                'rol'             => $user->rol->rol,
                'identificador'   => ($user->role_id == 3) ? 'sin identificador' : $user->empresa?->cif,
                'motivo'          => $user->motivoBaja?->motivo ?? 'No especificado',
                'comentario'      => $user->comentario_baja,
                'fecha_de_baja'   => $user->fecha_baja,
            ];
        });
        return response()->json([
            'data' => $bajas,
            'message' => 'Historial recuperado con éxito'
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al recuperar historial','errors'=>$e->getMessage()], 500);
        }
    }
    /**
 * Guardar un nuevo motivo de baja (Solo Admin)
 */
public function storeMotivo(Request $request)
{
    try {
        // 1. La validación (Si falla, lanza una ValidationException automáticamente)
        $validatedData = $request->validate([
            'motivo' => 'required|string|max:255',
            'visible_alumno' => 'boolean',
            'visible_empresa' => 'boolean',
            'solo_admin' => 'boolean'
        ]);

        // 2. Preparar datos (Asegurar booleanos si no vienen en el request)
      $motivo = MotivoBaja::create([
            'motivo'          => $validatedData['motivo'],
            'visible_alumno'  => $request->input('visible_alumno', false),
            'visible_empresa' => $request->input('visible_empresa', false),
            'solo_admin'      => $request->input('solo_admin', false),
        ]);

        return response()->json([
            'message' => 'Motivo creado correctamente',
            'data' => $motivo
        ], 201); // 201 = Created

    } catch (\Illuminate\Validation\ValidationException $e) {
      
        return response()->json([
            'message' => 'Los datos enviados no son válidos',
            'errors' => $e->errors() 
        ], 422);

    } catch (\Exception $e) {
      
        return response()->json([
            'message' => 'Hubo un problema interno en el servidor',
            'error' => $e->getMessage() 
        ], 500);
    }
}

/**
 * Actualizar un motivo existente (Solo Admin)
 */
public function updateMotivo(Request $request, $id)
{
    try {
        $motivo = MotivoBaja::findOrFail($id);
        
        $request->validate([
            'motivo' => 'string|max:255',
            'visible_alumno' => 'boolean',
            'visible_empresa' => 'boolean',
            'solo_admin' => 'boolean'
        ]);

        $motivo->update($request->all());

        return response()->json([
            'message' => 'Motivo actualizado correctamente',
            'data' => $motivo
        ]);

    
    } catch (Exception $e) {
        return response()->json(['message' => 'Error al actualizar', 'errors' => $e->getMessage()], 500);
    }
}

/**
 * Eliminar un motivo (Solo Admin)
 */
public function destroyMotivo($id)
{
    try {
        $motivo = MotivoBaja::findOrFail($id);
        
        //  tiene usuarios asociados?
        if ($motivo->usuarios()->exists()) {
            //se desactiva
            $motivo->update(['activo' => false]);
            
            return response()->json([
                'message' => 'El motivo no se puede eliminar porque tiene historial, pero ha sido DESACTIVADO para nuevos usuarios.'
            ], 200); // Devolvemos 200 porque la acción de "quitarlo de en medio" se ha logrado
        }

        // Si no tiene usuarios, borrado físico real
 $motivo->update(['activo' => false]);        
        return response()->json([
            'message' => 'Motivo ha sido desactivado.'
        ]);

   
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error al procesar la eliminación', 'errors' => $e->getMessage()], 500);
    }
}
/**
 * Baja forzosa ejecutada por el Administrador.
 */
public function bajaPorAdmin(Request $request, $idUsuario)
{
    try {
        $user = User::findOrFail($idUsuario);

        DB::transaction(function () use ($user, $request) {
            
            // --- GESTIÓN DE EMPRESA ---
            if ($user->role_id == 2) { 
                // 1. Buscamos todas las ofertas que NO estén cerradas (ID 1 es Abierta)
                $ofertasIds = $user->empresa->ofertas()
                    ->where('estado_id', 1)
                    ->pluck('id');

                if ($ofertasIds->isNotEmpty()) {
                    // 2. Cerramos las ofertas (Cambiamos a ID 2, que es 'Cerrada')
                    $user->empresa->ofertas()->whereIn('id', $ofertasIds)->update([
                        'estado_id' => 2, 
                        'motivo_id' => 2, 
                        'fechaCierre' => now(),
                    ]);

                    // 3. Liberamos a los alumnos en la tabla pivote
                    DB::table('demandante_oferta')
                        ->whereIn('oferta_id', $ofertasIds)
                        ->where('proceso_id', '!=', 3) // No tocamos a contratados
                        ->update(['proceso_id' => 2]); // 2 = Proceso Cerrado
                }
            }

            // --- GESTIÓN DE ALUMNO ---
            if ($user->role_id == 3) {
                // Si el alumno se va por el admin, lo retiramos de procesos abiertos
                DB::table('demandante_oferta')
                    ->where('demandante_id', $user->demandante->id)
                    ->where('proceso_id', 1) // En proceso
                    ->update(['estado_candidato_id' => 8]); // 8 = Retirado (según tu código previo)
            }

            // --- EJECUCIÓN DE LA BAJA DEL USUARIO ---
            $user->update([
                'status' => UserEstado::INACTIVO->value,
                'validado' => false,
                'motivo_baja_id' => $request->motivo_baja_id,
                'comentario_baja' => "ACCIÓN ADMIN: " . ($request->comentario_baja ?? 'Sin comentarios'),
                'fecha_baja' => now()
            ]);

            // Revocar sesiones
            $user->tokens()->delete();
        });

        return response()->json(['message' => 'Usuario desactivado y procesos limpiados correctamente.']);

    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error al procesar la baja administrativa',
            'errors' => $e->getMessage()
        ], 500);
    }
}
public function reactivarUsuario($idUsuario)
{
    try {
        // 1. Buscamos el usuario o lanzamos 404 si no existe
        $user = User::findOrFail($idUsuario);

        // 2. Ejecutamos la reactivación en una transacción
        DB::transaction(function () use ($user) {
            
            $user->update([
                'status'          => UserEstado::ACTIVO->value,
                'validado'        => true,
                'motivo_baja_id'  => null,
                'comentario_baja' => null,
                'fecha_baja'      => null
            ]);

        
        });

        return response()->json([
            'message' => 'Usuario reactivado con éxito. Ahora puede acceder al sistema.',
         
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Error: El usuario no existe en la base de datos.'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Hubo un error al intentar reactivar al usuario.',
            'errors'  => $e->getMessage()
        ], 500);
    }
}
}