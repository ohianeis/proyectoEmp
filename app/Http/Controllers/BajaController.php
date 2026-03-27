<?php

namespace App\Http\Controllers;

use App\Enums\UserEstado;
use App\Models\MotivoBaja;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(name="Bajas", description="Gestión de motivos, autogestión de bajas y auditoría administrativa")
 */
class BajaController extends Controller
{
    
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
     * @OA\Post(
     * path="/api/bajas/ejecutar",
     * summary="Darse de baja del portal (Usuario)",
     * description="El usuario se desactiva a sí mismo. Valida que no tenga procesos activos.",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * @OA\JsonContent(
     * required={"motivo_baja_id"},
     * @OA\Property(property="motivo_baja_id", type="integer"),
     * @OA\Property(property="comentario", type="string", maxLength=500)
     * )
     * ),
     * @OA\Response(response=200, description="Cuenta desactivada y sesión cerrada"),
     * @OA\Response(response=422, description="Error: Tiene ofertas o candidaturas activas")
     * )
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
    // Comprobar si tiene inscripciones en ofertas NO cerradas
    // solo contamos aquellas donde NO esté ya "Retirado" (ID 8)
    $tieneProcesosActivos = $user->demandante->ofertas()
        ->where('ofertas.estado_id', 1) // La oferta esta abierta
        ->wherePivot('estado_candidato_id', '!=', 8) // Que no esté ya retirado
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
     * @OA\Get(
     * path="/api/admin/bajas/historial",
     * summary="Historial de bajas (Admin)",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="busqueda", in="query", @OA\Schema(type="string")),
     * @OA\Response(response=200, description="Listado paginado de usuarios inactivos")
     * )
     */
    public function indexHistorialBajas(Request $request)
    {
       try {
        $busqueda=$request->query('busqueda');
        $rows = $request->query('rows', 10);
        $query = User::with(['rol:id,rol', 'motivoBaja:id,motivo', 'demandante:id,user_id', 'empresa:id,user_id,cif'])
            ->where('status', UserEstado::INACTIVO->value)
            ->whereNotNull('fecha_baja');

        //  APLICAL FILTRO (Si existe búsqueda)
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
     * @OA\Post(
     * path="/api/admin/bajas/motivos",
     * summary="Crear nuevo motivo de baja (Admin)",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=201, description="Motivo creado")
     * )
     */
public function storeMotivo(Request $request)
{
    try {
        //  La validación dara error
        $validatedData = $request->validate([
            'motivo' => 'required|string|max:255',
            'visible_alumno' => 'boolean',
            'visible_empresa' => 'boolean',
            'solo_admin' => 'boolean'
        ]);

        // Preparar datos (Asegurar booleanos si no vienen en el request)
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
     * @OA\Patch(
     * path="/api/admin/bajas/motivos/{id}",
     * summary="Actualizar un motivo de baja",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\JsonContent(
     * @OA\Property(property="motivo", type="string", example="Motivo actualizado"),
     * @OA\Property(property="visible_alumno", type="boolean", example=true),
     * @OA\Property(property="visible_empresa", type="boolean", example=false)
     * )
     * ),
     * @OA\Response(response=200, description="Motivo actualizado correctamente"),
     * @OA\Response(response=404, description="Motivo no encontrado")
     * )
     */
public function updateMotivo(Request $request, $id)
{
    try {
        $motivo = MotivoBaja::findOrFail($id);
        //validacion
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
     * @OA\Delete(
     * path="/api/admin/bajas/motivos/{id}",
     * summary="Eliminar o desactivar un motivo de baja",
     * description="Si el motivo tiene historial, solo se desactiva. Si no, se podría eliminar.",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Acción realizada correctamente")
     * )
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
            ], 200); 
        }

        // Si no tiene usuarios
 $motivo->update(['activo' => false]);        
        return response()->json([
            'message' => 'Motivo ha sido desactivado.'
        ]);

   
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error al procesar la eliminación', 'errors' => $e->getMessage()], 500);
    }
}
/**
     * @OA\Post(
     * path="/api/admin/bajas/forzosa/{idUsuario}",
     * summary="Baja administrativa de un usuario",
     * description="Desactiva al usuario, cierra sus ofertas (si es empresa) o retira candidaturas (si es alumno).",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="idUsuario", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\JsonContent(
     * required={"motivo_baja_id"},
     * @OA\Property(property="motivo_baja_id", type="integer", example=1),
     * @OA\Property(property="comentario_baja", type="string", example="Baja por incumplimiento de normas")
     * )
     * ),
     * @OA\Response(response=200, description="Usuario y procesos gestionados correctamente"),
     * @OA\Response(response=403, description="No puedes darte de baja a ti mismo")
     * )
     */
public function bajaPorAdmin(Request $request, $idUsuario)
{
    try {
        $user = User::findOrFail($idUsuario);
        // Seguridad extra: que el Admin no se borre a sí mismo
        if (Auth::id() === $user->id) {
            return response()->json(['message' => 'No puedes darte de baja a ti mismo.'], 403);
        }
        DB::transaction(function () use ($user, $request) {
            
            // --- GESTIÓN DE EMPRESA ---
            if ($user->role_id == 2) { 
                // Buscar todas las ofertas que NO estén cerradas (ID 1 es Abierta)
                $ofertasIds = $user->empresa->ofertas()
                    ->where('estado_id', 1)
                    ->pluck('id');

                if ($ofertasIds->isNotEmpty()) {
                    //. Cerrar las ofertas (Cambiar a ID 2, que es 'Cerrada')
                    $user->empresa->ofertas()->whereIn('id', $ofertasIds)->update([
                        'estado_id' => 2, 
                        'motivo_id' => 2, 
                        'fechaCierre' => now(),
                    ]);

                    //  Liberr a los alumnos en la tabla pivote
                    DB::table('demandante_oferta')
                        ->whereIn('oferta_id', $ofertasIds)
                        ->where('proceso_id', '!=', 3) // No tocar contratados
                        ->update(['proceso_id' => 2]); //  Proceso Cerrado
                }
            }

            // --- GESTIÓN DE ALUMNO ---
            if ($user->role_id == 3) {
                // Si el alumno se va por el admin, retirar de procesos abiertos
                DB::table('demandante_oferta')
                    ->where('demandante_id', $user->demandante->id)
                    ->where('proceso_id', 1) // En proceso
                    ->update(['estado_candidato_id' => 8]); // 8 = Retirado 
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
/**
     * @OA\Patch(
     * path="/api/admin/usuarios/{idUsuario}/reactivar",
     * summary="Reactivar un usuario inactivo (Admin)",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="idUsuario", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Usuario reactivado y datos de baja limpiados"),
     * @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
public function reactivarUsuario($idUsuario)
{
    try {
        //  Buscar el usuario 
        $user = User::findOrFail($idUsuario);

        //  Ejecutar la reactivación en una transacción
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
/**
     * @OA\Patch(
     * path="/api/admin/usuarios/{idUsuario}/reset-password",
     * summary="Resetear contraseña de usuario (Admin)",
     * description="Genera una clave de 8 caracteres y activa el flag change_pass.",
     * tags={"Bajas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="idUsuario", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Clave temporal generada")
     * )
     */
public function changePassAdmin(Request $request, $idUsuario)
{
    try {
        $user = User::findOrFail($idUsuario);

        // generar clave temporal aleatoria de 8 caracteres
        //  Str::random para ello
        $passwordTemporal = Str::random(8); 

        // 2. Actualizamos al usuario
        $user->update([
            'password' => Hash::make($passwordTemporal),
            'change_pass' => true
        ]);

       return response()->json([
            'message' => 'Contraseña reseteada con éxito',
            'data' => [
                'pass_temporal' => $passwordTemporal,
                'usuario' => $user->name,
                'email' => $user->email,
                'change_pass' => true
            ]
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error al resetear la contraseña',
            'errors' => $e->getMessage()
        ], 500);
    }
}

/**
     * @OA\Patch(
     * path="/api/usuarios/actualizar-password",
     * summary="Actualizar contraseña por cambio forzoso (Usuario)",
     * description="Limpia el flag change_pass tras la actualización exitosa.",
     * tags={"Auth"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * @OA\JsonContent(
     * required={"password","password_confirmation"},
     * @OA\Property(property="password", type="string", format="password", minLength=6),
     * @OA\Property(property="password_confirmation", type="string", format="password")
     * )
     * ),
     * @OA\Response(response=200, description="Contraseña actualizada")
     * )
     */
public function changePassUser(Request $request)
{
    try {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        //validacion
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        // Actualizar y liberar el bloqueo
        $user->update([
            'password' => Hash::make($request->password),
            'change_pass' => false
        ]);

       return response()->json([
            'message' => 'Contraseña actualizada correctamente',
            'data' => [
                'status' => 'success',
                'user_id' => $user->id
            ]
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'message' => 'No se pudo actualizar la contraseña',
            'errors' => $e->getMessage()
        ], 500);
    }
}
}