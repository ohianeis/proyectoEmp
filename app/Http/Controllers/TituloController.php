<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\DemandanteTitulo;
use App\Models\Nivele;
use App\Models\Titulo;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(name="Títulos", description="Endpoints para la gestión de títulos académicos")
 */
class TituloController extends Controller
{
 /**
     * @OA\Get(
     * path="/api/titulos",
     * summary="Listar todos los títulos",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista recuperada con éxito.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="titulo", type="string"),
     * @OA\Property(property="estado", type="string"),
     * @OA\Property(property="nivel", type="string"),
     * @OA\Property(property="familia", type="string")
     * ))
     * )
     * )
     * )
     */
    public function index()
    {


// Obtenemos títulos con sus relaciones para evitar el problema N+1
        try {
            $titulos = \App\Models\Titulo::with(['nivel', 'familia'])
                ->orderBy('familia_id') // Agrupar por familia queda más ordenado
                ->get()
                ->map(function ($titulo) {
                    return [
                        'id' => $titulo->id,
                        'titulo' => $titulo->nombre,
                        'estado' => $titulo->activado ? 'activo' : 'inactivo',
                        'nivel' => $titulo->nivel->nivel,
                        'familia' => $titulo->familia->nombre, // <--- NUEVO
                    ];
                });

            return response()->json([
                'message' => 'Lista de títulos recuperada con éxito',
                'data' => $titulos
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Erro al obtener los datos'], 500);
        }
    }
/**
     * @OA\Get(
     * path="/api/titulos/niveles/listado",
     * summary="Obtener niveles educativos",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Niveles recuperados.")
     * )
     */
    public function nivel()
    {
        try {
            $nivelesTitulos = Nivele::select('id', 'nivel')->get();
            return response()->json([
                'message' => 'Niveles titulos recuperados',
                'data' => $nivelesTitulos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro al obtener los niveles de los títulos'
            ], 500);
        }
    }

/**
     * @OA\Get(
     * path="/api/titulos/{titulo}",
     * summary="Ver detalle de un título",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="titulo", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle obtenido."),
     * @OA\Response(response=404, description="No encontrado.")
     * )
     */
    public function show(Titulo $titulo)
    {
        try {


            // Cargar las relaciones 'nivel' y 'centro'
            $titulo->load('nivel', 'centro');


            $response = [
                'id' => $titulo->id,
                'nombre' => $titulo->nombre,
                'nivel' => [
                    'id' => $titulo->nivel->id,
                    'nivel' => $titulo->nivel->nivel, // Campo 'nivel' desde la relación
                    'url' => "/api/niveles",
                ],
                'centro' => [
                    'id' => $titulo->centro->id,
                    'nombre' => $titulo->centro->nombre, // Campo 'nombre' desde la relación 'centro'

                ],
                'created_at' => $titulo->created_at->format('d-m-Y'), // Formato de fecha legible
                'updated_at' => $titulo->updated_at->format('d-m-Y')  // Formato de fecha legible
            ];

            return response()->json([
                'message' => 'Detalle titulo obtenido correctamente',
                'data' => $response
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'mesagge' => 'Error al obtener los datos',
                'errors'=>'Error'
            ], 500);
        }
    }

   /**
     * @OA\Post(
     * path="/api/titulos",
     * summary="Crear nuevo título",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * @OA\JsonContent(
     * required={"nombre","nivel","familia","centro"},
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="nivel", type="integer"),
     * @OA\Property(property="familia", type="integer"),
     * @OA\Property(property="centro", type="integer")
     * )
     * ),
     * @OA\Response(response=201, description="Creado.")
     * )
     */
    public function store(Request $request)
    {
// Verificar duplicados antes de validar para ahorrar procesamiento   
     $existeTitulo = Titulo::where('nombre', $request->nombre)->where('nivele_id', $request->nivel)->exists();
        if ($existeTitulo) {
            return response()->json([
                'mensaje' => 'Título ya existente',
            ], 409);
        } else {
            try {
                $validacion = $request->validate([
                    'nombre' => 'required|string|max:255',
                    'nivel' => 'required|integer|exists:niveles,id', //exista en la tabla niveles el dato
                    'familia' => 'required|integer|exists:familias,id', //tabla familias profesionales
                    'centro' => 'required|integer|exists:centros,id',

                ]);
                $nuevoRegistro = [];
                if ($validacion) {
                    $nuevoRegistro['nombre'] = strtolower($request['nombre']);
                    $nuevoRegistro['activado'] = 1;
                    $nuevoRegistro['nivele_id'] = $request['nivel'];
                    $nuevoRegistro['familia_id'] = $request['familia'];
                    $nuevoRegistro['centro_id'] = $request['centro'];
                }
                Titulo::create($nuevoRegistro);
                return response()->json([
                    'data' => $nuevoRegistro,
                    'message' => 'Título creado correctamente'
                ], 201);
            } catch (ValidationException $e) {
                return response()->json([

                    'message' => 'Los datos proporcionados no son válidos.', // Mensaje general para el Toast
                    'errors'  => $e->errors() // Detalles específicos para cada campo del formulario
                ], 422);
            } catch (Exception $e) {
                return response()->json([
                    'errors' => 'Error al crear el título',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }
  /**
     * @OA\Get(
     * path="/api/titulos/activos",
     * summary="Listar solo títulos activos",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Éxito.")
     * )
     */
 public function titulosActivos()
{
    try {
        // Añadimos familia_id y nivele_id al select
     $titulos = Titulo::with(['nivel:id,nivel']) // Trae solo id y nombre del nivel relacionado
            ->select('id', 'nombre', 'familia_id', 'nivele_id')
            ->where('activado', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'message' => 'Datos obtenidos correctamente',
            'data' => $titulos
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage()
        ], 500);
    }
}


 /**
     * @OA\Patch(
     * path="/api/titulos/{titulo}",
     * summary="Actualizar título existente",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="titulo", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Actualizado.")
     * )
     */
    public function update(Request $request, Titulo $titulo)
    {
        //
        try {

            // Si el título está inactivo y el request NO viene a activarlo, bloqueamos la edición.
        if ($titulo->activado == 0 && !$request->has('activado')) {
            return response()->json([
                'message' => 'El título está desactivado. Actívelo primero para poder editar sus datos.'
            ], 403);
        }

        //  COMPROBACIÓN DE FAMILIA:
        // Si el request intenta activar el título, verificamos que su familia esté activa.
        if ($request->activado == 1) {
            // Cargamos la relación familia si no está cargada
            $familia = $titulo->familia; 
            if ($familia && !$familia->activa) {
                return response()->json([
                    'message' => 'No se puede activar el título porque la Familia Profesional "' . $familia->nombre . '" está desactivada.'
                ], 422); // Unprocessable Entity
            }
        }
            $validacion = $request->validate([
                'id' => 'integer|in:' . $titulo->id, //sea el mismo que el id a actualizar no se haya cambiado
                'nombre' => 'required|string|max:255',
                'nivel' => 'required|integer|exists:niveles,id', //exista en la tabla niveles el dato
                'centro' => 'required|integer|exists:centros,id',

            ]);
            //actualizar datos
            if (isset($validacion['nombre'])) {
                $titulo->nombre = strtolower($validacion['nombre']);
            }
            if (isset($validacion['nivel'])) {
                $titulo->nivele_id = $validacion['nivel'];
            }
            if (isset($validacion['centro'])) {
                $titulo->centro_id = $validacion['centro'];
            }
            // Si el request trae el campo 'activado', lo actualizo para poder pasar a activo un titulo inactivo
            if ($request->has('activado')) {
                $titulo->activado = $request->activado;
            }
            $titulo->save();
            return response()->json([
                'message' => 'Titulo actualizado correctamente',
                'data' => $titulo
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'error al actualizar el titulo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

  /**
     * @OA\Delete(
     * path="/api/titulos/{titulo}",
     * summary="Desactivar título",
     * tags={"Títulos"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="titulo", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Desactivado.")
     * )
     */
    public function destroy(Titulo $titulo)
    {
        //


        try {
            // Marcamor como inactivo en cualquier caso
            $titulo->activado = 0;
            $titulo->save(); 

            // Comprobamos si tiene relaciones solo para personalizar
            $tieneRelaciones = $titulo->ofertas()->exists() || $titulo->demandantes()->exists();

            $message = $tieneRelaciones
                ? 'El título tiene historial asociado. Se ha marcado como inactivo para preservar los datos.'
                : 'Título marcado como inactivo correctamente.';

            return response()->json([
                'data' => $titulo,
                'message' => $message
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al procesar el borrado'], 500);
        }
    }

  /**
     * @OA\Post(
     * path="/api/titulos/demandante",
     * summary="Vincular títulos a un perfil de demandante",
     * tags={"Títulos-Demandante"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * @OA\JsonContent(
     * @OA\Property(property="titulos", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="centro", type="string"),
     * @OA\Property(property="anio", type="integer"),
     * @OA\Property(property="cursando", type="boolean")
     * ))
     * )
     * ),
     * @OA\Response(response=201, description="Vinculados.")
     * )
     */
    public function agregarTitulos(Request $request)
    {
        $demandante = Demandante::where('user_id', Auth::user()->id)->first();
        try {
            $validacion = $request->validate([
                'titulos' => 'required|array',
                'titulos.*.id' => 'exists:titulos,id',
                'titulos.*.centro' => 'required|string|max:255',
                'titulos.*.anio' => 'required|integer|min:1900|max:' . date('Y'), // Cambiar "año" por "anio"
                'titulos.*.cursando' => 'required|boolean',
            ]);
// Filtrar títulos que el usuario ya tiene para evitar errores de clave duplicada
            $titulosNoDuplicados = collect($validacion['titulos'])->filter(function ($titulo) use ($demandante) {
                return !DemandanteTitulo::where('demandante_id', $demandante->id)
                    ->where('titulo_id', $titulo['id'])
                    ->exists(); // Comprobar si el título ya está asociado
            });

            if ($titulosNoDuplicados->isEmpty()) {
                return response()->json(['message' => 'Todos los títulos ya están vinculados al demandante.'], 400);
            }

            foreach ($titulosNoDuplicados as $titulo) {
                $demandante->titulos()->attach($titulo['id'], [
                    'centro' => $titulo['centro'],
                    'año' => $titulo['anio'],
                    'cursando' => $titulo['cursando'],
                ]);
            }
            return response()->json(['message' => 'Titulo/s asociados correctamente'], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Los datos introducidos no son válidos',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
  /**
     * @OA\Get(
     * path="/api/titulos/demandante",
     * summary="Ver mis títulos (Demandante)",
     * tags={"Títulos-Demandante"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Lista.")
     * )
     */

    public function titulosDemandante()
    {
        try {
            $demandante = Auth::user()->demandante;
            // Transformar la colección para incluir datos de la tabla pivote
            $misTitulos = $demandante->titulos->map(function ($titulo) {
                return [
                    'id' => $titulo->pivot->id, // <---  ID  para el DELETE
                    'titulo_id' => $titulo->id,//id real del titulo para el filtrado en front
                    'nombre' => $titulo->nombre,
                    'año' => $titulo->pivot->año,
                    'centro' => $titulo->pivot->centro,
                    'cursando' => (bool)$titulo->pivot->cursando,
                    'activado' => $titulo->activado
                ];
            });

            return response()->json([
                'message' => 'Títulos obtenidos correctamente',
                'data' => $misTitulos
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
/**
     * @OA\Delete(
     * path="/api/titulos/demandante/{id}",
     * summary="Eliminar un título del demandante",
     * tags={"Títulos-Demandante"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=201, description="Eliminado.")
     * )
     */
    public function tituloDemandante(Request $request)
    {

        try {
            $demandante = Demandante::where('user_id', Auth::user()->id)->first();


            // Obtener el ID del título desde el request
            $id = $request->id;


            $registro = DemandanteTitulo::where('id', $id)
                ->where('demandante_id', $demandante->id)
                ->first();

            if ($registro) {
                $registro->delete(); // Eliminar exclusivamente de la tabla pivot
                return response()->json(['message' => 'El título ha sido eliminado del demandante.'], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }
    }

  /**
     * @OA\Get(
     * path="/api/familias",
     * summary="Listado familias",
     * tags={"Configuración"},
     * @OA\Response(response=200, description="Éxito.")
     * )
     */
    public function familias()
    {
        try {
            $familias = \App\Models\Familia::select('id', 'nombre', 'activa')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'message' => 'Familias recuperadas',
                'data' => $familias
            ], 200);
        } catch (Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }
 /**
     * @OA\Post(
     * path="/api/familias",
     * summary="Crear familia",
     * tags={"Configuración"},
     * @OA\RequestBody(@OA\JsonContent(@OA\Property(property="nombre", type="string"))),
     * @OA\Response(response=201, description="Creada.")
     * )
     */
    public function storeFamilia(Request $request)
    {
        try {
            $validacion = $request->validate([
                'nombre' => 'required|string|max:255|unique:familias,nombre',
            ], [
                'nombre.unique' => 'Ya existe una familia profesional con ese nombre.'
            ]);

            $familia = \App\Models\Familia::create([
                'nombre' => $validacion['nombre'],
                'activa' => true
            ]);

            return response()->json([
                'message' => 'Familia profesional creada con éxito',
                'data' => $familia
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['errors' => 'Error al crear la familia'], 500);
        }
    }

  /**
     * @OA\Put(
     * path="/api/familias/{id}",
     * summary="Actualizar familia",
     * tags={"Configuración"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Éxito.")
     * )
     */
    public function updateFamilia(Request $request, $id)
    {
        try {
       $familia = \App\Models\Familia::findOrFail($id);

        // Si está desactivada Y el request no intenta activarla, bloqueamos.
        if (!$familia->activa && !$request->has('activa')) {
            return response()->json([
                'message' => 'La familia está desactivada. Primero debe reactivarla para editar sus datos.',
            ], 403); 
        }

        $validacion = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:familias,nombre,' . $id,
            'activa' => 'sometimes|boolean'
        ]);

        $familia->update($validacion);

        // Lógica de cascada: Si reactivamos la familia, no se reactiva titulos

        if ($familia->activa) {
            // $familia->titulos()->update(['activo' => true]);
        }

        return response()->json([
            'message' => 'Familia actualizada correctamente, revise los títulos uno a uno que quiera volver a reactivar.',
            'data' => $familia
        ], 200);
        } catch (Exception $e) {
            return response()->json(['errors' => 'Error al actualizar la familia'], 500);
        }
    }

/**
     * @OA\Delete(
     * path="/api/familias/{id}",
     * summary="Inactivar familia",
     * tags={"Configuración"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Éxito.")
     * )
     */
    public function destroyFamilia($id)
    {
        try {
            $familia = \App\Models\Familia::findOrFail($id);

          // 1. Desactivamos la familia
        $familia->activa = false;
        $familia->save();

        // 2. Desactivamos todos sus títulos asociados de golpe
        // Esto asume que el modelo Titulo tiene una columna 'activo'
        $familia->titulos()->update(['activado' => false]); 

        return response()->json([
            'message' => 'Familia y sus títulos asociados desactivados correctamente.',
            'data' => $familia
        ], 200);
        } catch (Exception $e) {
            return response()->json(['errors' => 'Error al desactivar la familia'], 500);
        }
    }
}
