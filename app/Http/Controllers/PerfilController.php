<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\Direccione;
use App\Models\Empresa;
use App\Models\Situacione;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationData;
use OpenApi\Annotations as OA;

use function PHPUnit\Framework\isEmpty;

/**
 * @OA\Tag(
 * name="Perfil",
 * description="Operaciones relacionadas con el perfil de usuario, empresa y demandante"
 * )
 */
class PerfilController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/perfil",
     * operationId="getPerfilDetallado",
     * tags={"Perfil"},
     * summary="Obtener perfil del usuario",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Éxito",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Perfil cargado"),
     * @OA\Property(property="data", type="object", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="No autenticado"
     * )
     * )
     */
    public function index()
    {


        try {
            /** @var \App\Models\User $usuario */
            $usuario = Auth::user();
            $perfil = null;

            if ($usuario->role_id == 2) {
                $perfil = $usuario->empresa()->with(['direccion', 'centro'])->first();
            } else if ($usuario->role_id == 3) {
                $perfil = $usuario->demandante()->with(['direccion', 'situacion', 'centro'])->first();
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil cargado con éxito',
                'data'    => $perfil
            ], 200);
        } catch (Exception $e) {
            // Si algo falla, el Frontend recibe un mensaje claro en lugar de un error de sistema
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el perfil',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Post(
     * path="/api/perfil/direccion",
     * summary="Guardar o actualizar dirección",
     * tags={"Perfil"},
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"linea1", "ciudad", "provincia", "codigoPostal", "visible"},
     * @OA\Property(property="linea1", type="string"),
     * @OA\Property(property="linea2", type="string", nullable=true),
     * @OA\Property(property="ciudad", type="string"),
     * @OA\Property(property="provincia", type="string"),
     * @OA\Property(property="codigoPostal", type="string"),
     * @OA\Property(property="visible", type="boolean")
     * )
     * ),
     * @OA\Response(response=201, description="Creado"),
     * @OA\Response(response=200, description="Actualizado"),
     * @OA\Response(response=422, description="Error validación")
     * )
     */
    public function store(Request $request)
    {
        try {
            $usuario = Auth::user();

            // Buscamos si ya tiene dirección (Empresa o Demandante)
            $direccion = ($usuario->role_id == 2)
                ? $usuario->empresa->direccion
                : $usuario->demandante->direccion;

            // Validamos los datos (común para crear y editar)
            $validacion = $request->validate([
                'linea1'       => 'required|string|max:255',
                'linea2'       => 'nullable|string|max:255',
                'ciudad'       => 'required|string|max:100',
                'provincia'    => 'required|string|max:100',
                'codigoPostal' => 'required|string|max:10',
                'visible'      => 'required|boolean'
            ]);

            if (is_null($direccion)) {
                //  CREAR (Store)
                $direccion = new Direccione($validacion);

                if ($usuario->role_id == 2) {
                    $usuario->empresa->direccion()->save($direccion);
                } else {
                    $usuario->demandante->direccion()->save($direccion);
                }

                return response()->json(['message' => 'Dirección creada correctamente'], 201);
            } else {
                //  ACTUALIZAR
                // Pasamos los datos validados al método de actualización
                return $this->actualizarDireccion($validacion, $direccion);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (Exception $e) {
            return response()->json([

                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function actualizarDireccion(array $datos, Direccione $direccion)
    {
        try {
            // Comparamos para ver si hay cambios reales
            $camposActuales = $direccion->only(['linea1', 'linea2', 'ciudad', 'provincia', 'codigoPostal', 'visible']);
            $diferencias = array_diff_assoc($datos, $camposActuales);

            if (empty($diferencias)) {
                return response()->json(['message' => 'No hay cambios que guardar.'], 200);
            }

            $direccion->update($datos);

            return response()->json(['message' => 'Dirección actualizada correctamente'], 200);
        } catch (Exception $e) {
            return response()->json([

                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Patch(
     * path="/api/perfil/editar",
     * summary="Actualizar datos del perfil",
     * tags={"Perfil"},
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="cif", type="string", nullable=true),
     * @OA\Property(property="localidad", type="string", nullable=true),
     * @OA\Property(property="telefono", type="string", nullable=true),
     * @OA\Property(property="experienciaLaboral", type="string", nullable=true),
     * @OA\Property(property="situacion", type="integer", nullable=true)
     * )
     * ),
     * @OA\Response(response=201, description="Actualizado correctamente"),
     * @OA\Response(response=422, description="Error validación")
     * )
     */
    public function update(Request $request)
    {
        try {
            $usuario = Auth::user();
            $rol = $usuario->role_id;
            $perfil = null;
            $validacion = [];

            if ($rol == 2) {
                $perfil = $usuario->empresa;
                $validacion = $request->validate([
                    'nombre'            => 'required|string|max:255',
                    'cif'               => 'nullable|string|max:15|unique:empresas,cif,' . $perfil->id,
                    'localidad'         => 'nullable|string|max:100',
                    'descripcion'       => 'nullable|string|max:2000',
                    'web'               => 'nullable|string|max:255',
                    'telefono_contacto' => 'nullable|string|max:20'
                ]);

                // CAMPOS A COMPARAR PARA EMPRESA
                $camposActuales = $perfil->only(['nombre', 'cif', 'localidad', 'descripcion', 'web', 'telefono_contacto']);
            } else if ($rol == 3) {
                $perfil = $usuario->demandante;
                $validacion = $request->validate([
                    'nombre'             => 'required|string|max:100',
                    'telefono'           => 'nullable|string|max:20',
                    'experienciaLaboral' => 'nullable|string|max:2000',
                    'situacion'          => 'nullable|integer|exists:situaciones,id'
                ]);

                // Mapeo manual para demandante (situacion vs situacione_id), lo hago aqui por relacion situacion_id
                $camposActuales = [
                    'nombre'             => $perfil->nombre,
                    'telefono'           => $perfil->telefono,
                    'experienciaLaboral' => $perfil->experienciaLaboral,
                    'situacion'          => $perfil->situacione_id
                ];
            }

            // CONTROL DE CAMBIOS: Comparamos lo que llega con lo que hay
            $diferencias = array_diff_assoc($validacion, $camposActuales);

            if (empty($diferencias)) {
                return response()->json([

                    'message' => 'No hay cambios que guardar'
                ], 200); // Enviamos 200 para que Angular lo trate como éxito pero con mensaje de aviso
            }

            // Si hay cambios, guardamos
            if ($rol == 2) {
                $perfil->fill($validacion);
            } else {
                $perfil->fill([
                    'nombre'             => $validacion['nombre'],
                    'telefono'           => $validacion['telefono'],
                    'experienciaLaboral' => $validacion['experienciaLaboral'],
                    'situacione_id'      => $validacion['situacion'],
                ]);
            }

            $perfil->save();

            return response()->json([

                'message' => 'Perfil actualizado correctamente',

            ], 201);
        } catch (ValidationException $e) {
            return response()->json([

                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (Exception $e) {
            return response()->json([

                'message' => 'Error al actualizar el perfil',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/perfil/situaciones",
     * summary="Listar situaciones",
     * tags={"Perfil"},
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Lista de situaciones",
     * @OA\JsonContent(type="array", @OA\Items(type="object"))
     * )
     * )
     */
    public function listarSituaciones()
    {
        try {
            $situaciones = Situacione::obtenerTodas();
            return response()->json($situaciones, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener las situaciones.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
