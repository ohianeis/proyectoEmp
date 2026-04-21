<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\DemandanteOferta;
use App\Models\EstadoCandidato;
use App\Models\Motivo;
use App\Models\Oferta;
use App\Models\Proceso;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

/**
 * @OA\Tag(name="Ofertas", description="Gestión de ofertas de trabajo")
 */
class OfertaController extends Controller
{

    /**
     * @OA\Get(
     * path="/api/ofertas",
     * summary="Listar ofertas (Empresa o Alumno)",
     * tags={"Ofertas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Lista de ofertas obtenida",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean"),
     * @OA\Property(property="data", type="object"),
     * @OA\Property(property="counts", type="object")
     * )
     * ),
     * @OA\Response(response=401, description="No autenticado"),
     * @OA\Response(response=500, description="Error de servidor")
     * )
     */

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            // Determinamos si cargamos perfil de empresa o demandante
            $queUsuario = ($user->role_id == 2) ? $user->empresa : $user->demandante;
            $perPage = $request->input('per_page', 10); // Recogemos el parámetro de Angular de pagina
            if ($user->role_id == 2) {
                // LOGICA EMPRESA: Ver sus propias ofertas
                $estado = $request->input('estado');
                $totalAbiertas = Oferta::where('empresa_id', $queUsuario->id)->where('estado_id', 1)->count();
                $totalCerradas = Oferta::where('empresa_id', $queUsuario->id)->where('estado_id', 2)->count();
                //datos para empresa
                $query = Oferta::with(['familia'])
                    ->withCount('demandantes')
                    ->where('empresa_id', $queUsuario->id)
                    ->orderBy('created_at', 'desc');
                // Filtramos dinámicamente según la pestaña
                if ($estado === 'abierta') {
                    $query->where('estado_id', 1);
                } elseif ($estado === 'cerrada') {
                    $query->where('estado_id', 2);
                }

                $paginador = $query->paginate($perPage);
                $paginador->through(function ($oferta) {
                    return [
                        'id' => $oferta->id,
                        'nombre' => $oferta->nombre,
                        'familia' => $oferta->familia->nombre ?? 'Perfil General',
                        'tipoContrato' => $oferta->tipoContrato,
                        'horario' => $oferta->horario,
                        'estado_id' => ($oferta->estado_id == 1) ? 'Abierta' : 'Cerrada',
                        'esAnonima' => (bool)$oferta->esAnonima,
                        'demandantesInscritos' => $oferta->demandantes_count,
                        'created_at' => $oferta->created_at
                    ];
                });
                return response()->json([
                    'success' => true,
                    'message' => 'Ofertas de empresa cargadas',
                    'data' => $paginador,
                    'counts' => [
                        'abiertas' => $totalAbiertas,
                        'cerradas' => $totalCerradas
                    ]
                ], 200);
            } else if ($user->role_id == 3) {
                // LOGICA ALUMNO: Ver ofertas afines a su perfil
                $misTitulosIds = $queUsuario->titulos->pluck('id')->toArray();
                $misFamiliasIds = $queUsuario->titulos->pluck('familia_id')->unique()->toArray();


                $paginador = Oferta::with(['familia', 'empresa'])
                    ->where('estado_id', 1)
                    ->whereHas('empresa.user', function ($q) {
                        $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                            ->where('validado', true);
                    })
                    // Excluimos donde el alumno ya está inscrito
                    ->whereDoesntHave('demandantes', fn($q) => $q->where('demandante_id', $queUsuario->id))
                    ->where(function ($query) use ($misTitulosIds, $misFamiliasIds) {
                        $query->whereHas('titulos', fn($q) => $q->whereIn('titulos.id', $misTitulosIds))
                            ->orWhere(fn($q) => $q->whereIn('familia_id', $misFamiliasIds)->whereDoesntHave('titulos'));
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage); // <-- PAGINACIÓN AQUÍ

                //  Transformamos los datos sin romper el paginador usando through()
                $paginador->through(function ($oferta) use ($misTitulosIds) {
                    // Calculamos el % de coincidencia (Match) entre títulos alumno vs títulos oferta
                    $titulosOferta = $oferta->titulos()->pluck('titulos.id');
                    $match = ($titulosOferta->count() > 0)
                        ? round(($titulosOferta->intersect($misTitulosIds)->count() / $titulosOferta->count()) * 100)
                        : 100;

                    return [
                        'id' => $oferta->id,
                        'nombre' => $oferta->nombre,
                        'empresa_nombre' => $oferta->esAnonima ? "Empresa Confidencial" : $oferta->empresa->nombre,
                        'familia' => $oferta->familia->nombre,
                        'matchAfinidad' => $match,
                        'created_at' => $oferta->created_at,
                        'esAnonima' => (bool)$oferta->esAnonima
                    ];
                });

                return response()->json([
                    'success' => true, // Importante para tu interfaz ApiResponse
                    'message' => 'Ofertas cargadas',
                    'data' => $paginador // Enviamos el objeto paginador completo
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/ofertas/{oferta}",
     * summary="Ver detalle de una oferta",
     * tags={"Ofertas"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Detalle de oferta",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=409, description="No cumple requisitos"),
     * @OA\Response(response=404, description="No encontrada")
     * )
     */
    public function show(Oferta $oferta)
    {
        try {
            $user = Auth::user();
            $queUsuario = ($user->role_id == 2) ? $user->empresa : $user->demandante;

            // Cargamos relaciones necesarias para mostrar la info completa
            $ofertaInfo = Oferta::with(['empresa.direccion', 'titulos.nivel', 'motivo', 'estado', 'familia'])
                ->findOrFail($oferta->id);

            // para control match e inscrito
            $match = 0;
            $inscrito = false;

            // para info demandante
            if ($user->role_id == 3) {
                // CONTROL DE ACCESO ALUMNO: ¿Puede ver esta oferta?
                $misTitulosIds = $queUsuario->titulos->pluck('id')->toArray();

                $titulosOfertaIds = $ofertaInfo->titulos->pluck('id');

                if ($titulosOfertaIds->isNotEmpty()) {
                    $cumple = $titulosOfertaIds->intersect($misTitulosIds)->isNotEmpty();
                } else {
                    // Si es perfil general, comprobamos que el alumno tenga algún título de esa familia
                    $cumple = $queUsuario->titulos()
                        ->where('familia_id', $ofertaInfo->familia_id)
                        ->exists();
                }

                if (!$cumple) {
                    return response()->json(['message' => 'No cumples los requisitos para esta rama profesional.'], 409);
                }

                // Cálculo de afinidad específico para la vista de detalle
                $match = $titulosOfertaIds->count() > 0
                    ? round((count(array_intersect($misTitulosIds, $titulosOfertaIds->toArray())) / $titulosOfertaIds->count()) * 100)
                    : 100; // Si es perfil general de su familia, match es 100%

                $registro = $ofertaInfo->demandantes()->where('demandante_id', $queUsuario->id)->first();
                $inscrito = !is_null($registro);
            }

            // datos respuesta que usan tanto alumno como empresa
            $response = [
                'id'           => $ofertaInfo->id,
                'nombre'       => $ofertaInfo->nombre,
                'familia'       => $ofertaInfo->familia->nombre,
                'incorporacion' => $ofertaInfo->incorporacion,
                'esAnonima'   => $ofertaInfo->esAnonima,
                'observacion'  => $ofertaInfo->observacion,
                'tipoContrato' => $ofertaInfo->tipoContrato,
                'horario'      => $ofertaInfo->horario,
                'nPuestos'     => $ofertaInfo->nPuestos,
                'estado'       => $ofertaInfo->estado->tipo ?? 'Sin estado',
                'fechaCierre'  => $ofertaInfo->fechaCierre,
                'motivo'       => $ofertaInfo->motivo->tipo ?? 'Sin motivo',
                'titulos'      => $ofertaInfo->titulos->map(fn($t) => [
                    'nombre' => $t->nombre,
                    'nivel'  => $t->nivel->nivel ?? 'Sin nivel'
                ]),
                //controlo que el candidato este activo y validado por si se inscribio y luego se dio de baja, 
                'demandantesInscritos' => $ofertaInfo->demandantes()
                    ->whereHas('user', function ($q) {
                        $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                            ->where('validado', true);
                    })->count(),
                'created_at' => $ofertaInfo->created_at
            ];

            // añadir datos extra para alumno
            if ($user->role_id == 3) {
                // OCULTAR DATOS SI ES ANÓNIMA: Solo para alumnos
                $esAnonima = (bool)$ofertaInfo->esAnonima;
                if ($esAnonima) {
                    // si es anonima no se manda datos, por seguridad lo hago asi
                    $response['empresa'] = [
                        'nombre'      => 'Empresa Confidencial',
                        'ubicacion'   => 'No disponible',
                        'descripcion' => 'La identidad de la empresa se revelará en fases avanzadas del proceso.',
                        'web'         => null,
                        'direccion'   => null
                    ];
                } else {
                    // sin o es anonimo mando todo
                    $response['empresa'] = [
                        'id'          => $ofertaInfo->empresa->id,
                        'nombre'      => $ofertaInfo->empresa->nombre,
                        'ubicacion'   => $ofertaInfo->empresa->localidad ?? 'No disponible',
                        'descripcion' => $ofertaInfo->empresa->descripcion,
                        'web'         => $ofertaInfo->empresa->web,
                        'direccion'   => $this->formatDireccion($ofertaInfo->empresa->direccion)
                    ];
                }
                $response['matchAfinidad'] = $match; // Para el buscador
                // Info de inscripción si ya participa en ella
                if ($inscrito) {
                    $response['infoDemandante'] = [
                        'fechaInscripcion' => \Carbon\Carbon::parse($registro->pivot->fecha)->format('d/m/Y'),
                        'estadoProceso'    => Proceso::find($registro->pivot->proceso_id)->estado ?? 'Pendiente',
                        'porcentajeAfinidad' => $match
                    ];
                }
            }
            //datos para empresa
            if ($user->role_id == 2) {
                // PARA EMPRESA: Mostrar quién se llevó el puesto si está cerrada con éxito
                $response['candidatoAsignado'] = ($ofertaInfo->estado_id == 2 && $ofertaInfo->motivo_id == 1)
                    ? $ofertaInfo->demandantes()->wherePivot('proceso_id', 3)->first()?->id
                    : null;
            }

            return response()->json([
                'message' => 'Ofertas cargadas correctamente',
                'data' => $response
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Función auxiliar para no ensuciar el código principal
    // Controla la privacidad de la ubicación de la empresa
    private function formatDireccion($dir)
    {
        // Si no hay dirección o está marcada como no visible, devolvemos null
        // Así no aparecerá ni la ciudad ni la provincia 
        if (!$dir || !(bool)$dir->visible) {
            return null;
        }

        // Solo si es visible, mandamos los datos detallados
        return [
            'linea1'    => $dir->linea1,
            'ciudad'    => $dir->ciudad,
            'provincia' => $dir->provincia,
            'visible'   => true
        ];
    }
    /**
     * @OA\Post(
     * path="/api/ofertas",
     * summary="Crear nueva oferta",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="observacion", type="string"),
     * @OA\Property(property="tipoContrato", type="string"),
     * @OA\Property(property="horario", type="string"),
     * @OA\Property(property="nPuestos", type="integer"),
     * @OA\Property(property="familia_id", type="integer"),
     * @OA\Property(property="titulo", type="array", @OA\Items(type="integer")),
     * @OA\Property(property="incorporacion", type="string", format="date"),
     * @OA\Property(property="esAnonima", type="boolean")
     * )
     * ),
     * @OA\Response(response=201, description="Creada", @OA\JsonContent(@OA\Property(property="message", type="string"))),
     * @OA\Response(response=409, description="Ya existe"),
     * @OA\Response(response=422, description="Error validación"),
     * @OA\Response(response=500, description="Error servidor")
     * )
     */

    public function store(Request $request)
    {


        try {
            $usuario = Auth::user();
            $empresa = $usuario->empresa->id;
            // Validación de los campos de la oferta
            $validacion = $request->validate([
                'nombre' => 'required|string|max:45',
                'observacion' => 'required|string|max:2000',
                'tipoContrato' => 'required|string|max:45',
                'horario' => 'required|string|max:45',
                'fechaCierre' => 'exclude',
                'nPuestos' => 'required|integer',
                'motivo_id' => 'exclude',
                'estado_id' => 'exclude',
                'empresa_id' => 'exclude',
                'familia_id'    => 'required|integer|exists:familias,id',
                'titulo'        => 'nullable|array',
                'titulo.*' => 'integer|exists:titulos,id',
                'incorporacion' => 'nullable|date',
                'esAnonima' => 'nullable|boolean'
            ]);
            // Evitamor duplicados: misma empresa, mismo nombre y estado abierto
            $existeOferta = Oferta::where('nombre', $request['nombre'])
                ->where('tipoContrato', $request['tipoContrato'])
                ->where('estado_id', 1)
                ->where('horario', $request['horario'])
                ->where('nPuestos', $request['nPuestos'])
                ->where('empresa_id', $empresa)
                ->exists();
            if ($existeOferta) {
                return response()->json([
                    'message' => 'Ya existe una oferta con esos datos'
                ], 409);
            }
            $oferta = new Oferta();
            $oferta->nombre = $request['nombre'];
            $oferta->observacion = $request['observacion'];
            $oferta->tipoContrato = $request['tipoContrato'];
            $oferta->horario = $request['horario'];
            $oferta->nPuestos = $request['nPuestos'];
            $oferta->estado_id = 1;
            $oferta->empresa_id = $empresa;
            $oferta->incorporacion = $request->incorporacion;
            $oferta->esAnonima = $request->esAnonima ?? false;
            $oferta->familia_id = $request->familia_id;
            $oferta->save();
            //se controla si hay titulos ya que ahora no es obligatorio si la oferta se crea por familia
            if ($request->has('titulo') && is_array($request->titulo)) {
                $oferta->titulos()->attach($request->titulo);
            }
            return response()->json([
                'message' => 'oferta creada correctamente'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/ofertas/{id}/edit",
     * summary="Cargar datos para editar",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Datos cargados", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     * @OA\Response(response=404, description="No encontrada")
     * )
     */
    public function edit($id)
    {
        try {
            // Cargamos la oferta con sus títulos para el formulario de edición
            $oferta = Oferta::with('titulos:id')->findOrFail($id);

            return response()->json([
                'message' => 'Datos cargados correctamente',
                'data' => [
                    'oferta' => $oferta,
                    // Indicamos si la edición debe estar bloqueada porque ya hay alumnos inscritos
                    'bloqueado' => $oferta->tieneInscritos()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['errors' => 'La oferta no existe.'], 404);
        } catch (Exception $e) {
            return response()->json(['errors' => 'Error en la petición de editar'], 500);
        }
    }
    /**
     * @OA\Put(
     * path="/api/ofertas/{id}",
     * summary="Actualizar oferta",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(@OA\Property(property="nombre", type="string"), @OA\Property(property="observacion", type="string"))
     * ),
     * @OA\Response(response=200, description="Actualizada"),
     * @OA\Response(response=404, description="No encontrada"),
     * @OA\Response(response=500, description="Error servidor")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $oferta = Oferta::findOrFail($id);
            // Comprobamos si ya hay gente apuntada para restringir la edición
            $bloqueado = $oferta->tieneInscritos();

            // definir qué campos se pueden editar SIEMPRE
            $camposPermitidos = ['observacion', 'horario', 'nPuestos', 'incorporacion', 'esAnonima'];

            //  Si NO hay inscritos, añadimos los campos críticos
            if (!$bloqueado) {
                array_push($camposPermitidos, 'nombre', 'tipoContrato', 'familia_id');
            }

            // Solo filtramos los campos permitidos
            $data = $request->only($camposPermitidos);

            // Actualizamos la tabla principal
            $oferta->update($data);

            //  Lógica para los títulos (Muchos a Muchos)
            if (!$bloqueado && $request->has('titulo')) {
                $oferta->titulos()->sync($request->titulo);
            }

            //  Respuesta según el estado de bloqueo
            if ($bloqueado) {
                return response()->json([
                    'message' => 'La oferta tiene candidatos inscritos. Se han actualizado los campos permitidos, pero los datos académicos (Nombre, Familia, Títulos) ya no pueden editarlos.',

                ], 200);
            }

            return response()->json([
                'message' => 'Oferta actualizada con éxito.',

            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['errors' => 'No se encontró la oferta para actualizar.'], 404);
        } catch (\Exception $e) {
            // Si algo falla (BD, validación, etc.) capturamos el error
            return response()->json([
                'errors' => 'Ha ocurrido un error al actualizar la oferta.',

            ], 500);
        }
    }
//método para editar la oferta

    /**
     * @OA\Patch(
     * path="/api/ofertas/{id}/anonimato",
     * summary="Alternar anonimato",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Estado cambiado")
     * )
     */
    public function cambiarAnonimato($id)
    {

        try {
            $usuario = Auth::user();
            // Asegurar que la oferta pertenece a la empresa que hace la petición
            $oferta = Oferta::where('id', $id)
                ->where('empresa_id', $usuario->empresa->id)
                ->firstOrFail();

            // Aplicamos el "NOT" (!) al valor actual
            $oferta->esAnonima = !$oferta->esAnonima;
            $oferta->save();

            return response()->json([
                'message' => 'Visibilidad cambiada con éxito',

            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'No se pudo cambiar el estado'], 404);
        }
    }
    /**
     * @OA\Post(
     * path="/api/ofertas/{oferta}/apuntarse",
     * summary="Inscribir alumno en oferta",
     * tags={"Ofertas/Demandante"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=201, description="Inscrito correctamente"),
     * @OA\Response(response=422, description="No cumple requisitos o ya inscrito")
     * )
     */
    public function apuntarseOferta(Oferta $oferta)
    {
        try {
            $demandante = Auth::user()->demandante;
            if ($oferta->estado_id != 1) {
                return response()->json(['message' => 'La oferta ya no está activa'], 422);
            }

            // OBTENER LOS IDS DE LOS TÍTULOS Y LAS FAMILIAS DEL ALUMNO
            $misTitulosIds = $demandante->titulos->pluck('id')->toArray();
            $misFamiliasIds = $demandante->titulos->pluck('familia_id')->unique()->toArray();

            // titulos o familia
            // Buscamos si la oferta actual cumple alguna de las dos condiciones
            $esValidaParaMi = Oferta::where('id', $oferta->id)
                ->where(function ($query) use ($misTitulosIds, $misFamiliasIds) {
                    $query->whereHas('titulos', function ($q) use ($misTitulosIds) {
                        // Caso A: La oferta pide títulos específicos y yo tengo alguno
                        $q->whereIn('titulos.id', $misTitulosIds);
                    })
                        ->orWhere(function ($q) use ($misFamiliasIds) {
                            //  La oferta NO tiene títulos específicos pero  de la familia titulos
                            $q->whereIn('familia_id', $misFamiliasIds)
                                ->whereDoesntHave('titulos');
                        });
                })->exists();

            if (!$esValidaParaMi) {
                return response()->json([
                    'message' => 'Tu perfil profesional no encaja con los requisitos de esta oferta'
                ], 422);
            }
            $yaInscrito = $demandante->ofertas()->where('oferta_id', $oferta->id)->first();

            if ($yaInscrito) {
                $estadoActual = $yaInscrito->pivot->estado_candidato_id;

                // Ya está inscrito activamente
                if ($estadoActual != 8) {
                    return response()->json(['message' => 'Ya estás inscrito en esta oferta'], 422);
                }

                // Estaba RETIRADA (8) -> REACTIVAMOS
                $demandante->ofertas()->updateExistingPivot($oferta->id, [
                    'fecha' => now(),
                    'estado_candidato_id' => 1, // Volvemos a 'Inscrito'
                    'revisado' => false,         // Para que a la empresa le salga como NUEVO
                    'proceso_id' => 1            // Reset de proceso si fuera necesario
                ]);

                return response()->json([
                    'message' => 'Candidatura reactivada correctamente'
                ], 200);
            }

            // SI NO EXISTE REGISTRO PREVIO -> INSERTAMOS (Attach)
            $demandante->ofertas()->attach($oferta->id, [
                'fecha' => now(),
                'proceso_id' => 1,
                'estado_candidato_id' => 1,
                'revisado' => false
            ]);

            return response()->json([
                'message' => 'Te has inscrito correctamente a la oferta'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Delete(
     * path="/api/ofertas/{oferta}/desapuntarse",
     * summary="Retirar candidatura",
     * tags={"Ofertas/Demandante"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Candidatura retirada"),
     * @OA\Response(response=404, description="No inscrito")
     * )
     */
    public function desapuntarseOferta(Oferta $oferta)
    {
        try {
            $demandante = Auth::user()->demandante;

            // Buscamos la inscripción activa
            $inscripcion = $demandante->ofertas()
                ->where('oferta_id', $oferta->id)
                ->first();

            if (!$inscripcion) {
                return response()->json(['message' => 'No estás inscrito en esta oferta.'], 404);
            }

            // En lugar de borrar, actualizamos el estado al ID 8
            $demandante->ofertas()->updateExistingPivot($oferta, [
                'estado_candidato_id' => 8,
                'fecha' => now() //  guardar cuándo se desapuntó
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Candidatura retirada correctamente.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/ofertas/inscritas/listado",
     * summary="Listado de mis inscripciones",
     * description="Retorna ofertas filtradas por tab (activas, conseguidas, retiradas, finalizadas) con lógica de anonimato aplicada.",
     * tags={"Ofertas/Demandante"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="tab", in="query", required=false, @OA\Schema(type="string", default="activas")),
     * @OA\Response(response=200, description="Lista paginada de ofertas"),
     * @OA\Response(response=500, description="Error de servidor")
     * )
     */

    public function ofertasInscritas(Request $request)
    {
        try {
            $user = Auth::user();
            $demandante = $user->demandante;
            $filtro = $request->query('tab', 'activas');
            // Cargar títulos
            $misTitulosIds = $demandante->titulos->pluck('id')->toArray();
            $query = $demandante->ofertas()
                ->with(['empresa.direccion', 'estado', 'titulos', 'familia'])
                ->withCount('demandantes');

            //aplicar filtro para pestaña angular
            if ($filtro === 'activas') {
                $query->where('ofertas.estado_id', 1)
                    ->wherePivotNotIn('estado_candidato_id', [6, 8, 7])
                    ->wherePivot('proceso_id', '!=', [3, 2]);
            } elseif ($filtro === 'conseguidas') {
                $query->wherePivot('proceso_id', 3);
            } elseif ($filtro === 'retiradas') {
                $query->wherePivot('estado_candidato_id', 8);
            } elseif ($filtro === 'finalizadas') {
                $query->where(function ($q) {
                    $q->where('demandante_oferta.estado_candidato_id', 6)
                        ->orWhere('ofertas.estado_id', 2);
                })
                    ->wherePivotNotIn('proceso_id', [3, 1])
                    ->wherePivotNotIn('estado_candidato_id', [7, 8]);
            }
            //  la paginación
            $ofertasPaginadas = $query->orderBy('demandante_oferta.fecha', 'desc')
                ->paginate(10);


            if ($ofertasPaginadas->isEmpty()) {
                // Mandamos los stats aunque esté vacío para que las pestañas no desaparezcan
                return response()->json([
                    'success' => true,
                    'message' => 'No hay ofertas en esta categoría',
                    'data' => [
                        'data' => [],
                        'total' => 0,
                        'stats' => $this->getStats($demandante) // Función auxiliar abajo
                    ]
                ], 200);
            }

            $ofertasPaginadas->setCollection($ofertasPaginadas->getCollection()->map(function ($oferta) use ($misTitulosIds, $demandante) {
                $esAnonima = $oferta->esAnonima;
                // --- logica afinidad por titulos ---
                $titulosOfertaIds = $oferta->titulos->pluck('id')->toArray();
                $totalRequeridos = count($titulosOfertaIds);
                $porcentajeMatch = 100;
                if ($totalRequeridos > 0) {
                    // Match por títulos específicos
                    $coincidencias = array_intersect($misTitulosIds, $titulosOfertaIds);
                    $porcentajeMatch = round((count($coincidencias) / $totalRequeridos) * 100);
                } else {
                    // Match por Familia (Perfil General)
                    // Comprobamos si el alumno tiene algún título de la familia de la oferta
                    $perteneceAFamilia = $demandante->titulos()
                        ->where('familia_id', $oferta->familia_id)
                        ->exists();
                    $porcentajeMatch = $perteneceAFamilia ? 100 : 0;
                }
                $proceso = \App\Models\Proceso::find($oferta->pivot->proceso_id);
                // en que estado se encuentra el candidato dentro del proceso, visto, entrevista...
                $estadoCandidato = EstadoCandidato::find($oferta->pivot->estado_candidato_id);
                // --- LÓGICA DE PRIVACIDAD direccion empresa ---
                $direccionFiltrada = null;
                if ($oferta->empresa->direccion && !$esAnonima) {
                    $dir = $oferta->empresa->direccion;
                    $esVisible = (bool)$dir->visible;
                    $direccionFiltrada = [
                        'linea1'    => $esVisible ? $dir->linea1 : 'Dirección privada',
                        'ciudad'    => $dir->ciudad,
                        'provincia' => $dir->provincia,
                        'visible'   => $esVisible
                    ];
                }

                return [
                    'id' => $oferta->id,
                    'nombre' => $oferta->nombre,
                    'estado' => $oferta->estado_id == 1 ? 'abierta' : 'cerrada',
                    'familia' => $oferta->familia->nombre,
                    'esAnonima' => $oferta->esAnonima,
                    'incorporacion' => $oferta->incorporacion,
                    'observacion' => $oferta->observacion,
                    'tipoContrato' => $oferta->tipoContrato,
                    'horario' => $oferta->horario,
                    'nPuestos' => $oferta->nPuestos,
                    'titulos' => $oferta->titulos->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'nombre' => $t->nombre
                        ];
                    }),
                    'fechaCierre' => $oferta->fechaCierre,
                    'created_at' => $oferta->created_at,
                    'demandantesInscritos' => $oferta->demandantes_count,
                    'empresa' => [
                        'id'          => $esAnonima ? null : $oferta->empresa->id,
                        'nombre'      => $esAnonima ? 'Empresa Confidencial' : $oferta->empresa->nombre,
                        'descripcion' => $esAnonima ? 'La identidad de la empresa es privada para esta oferta.' : $oferta->empresa->descripcion,
                        'ubicacion'   => $oferta->empresa->localidad ?? $oferta->empresa->ubicacion,
                        'web'         => $esAnonima ? null : $oferta->empresa->web, // <-- Ocultamos la web también
                        'direccion'   => $esAnonima ? null : $direccionFiltrada      // <-- Forzamos null si es anónima
                    ],
                    'infoDemandante' => [
                        'fechaInscripcion' => $oferta->pivot->fecha,
                        'estadoProceso' => $proceso ? $proceso->estado : 'Inscrito',
                        'seguimientoCandidato' => $estadoCandidato ? $estadoCandidato->nombre : 'Inscrito',
                        'porcentajeAfinidad' => $porcentajeMatch,
                    ]
                ];
            }));

            $data = $ofertasPaginadas->toArray();

            // Meter los totales al mismo nivel que 'total', 'per_page', etc.
            $data['stats'] = $this->getStats($demandante);

            return response()->json([
                'success' => true,
                'message' => 'Ofertas recuperadas correctamente',
                'data'    => $data, // Aquí 'data' contiene tanto la paginación como las stats
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Calcula las estadísticas de participación del demandante en diferentes ofertas.
     * Utilizado para mostrar contadores en la vista de gestión.
     * * @param Demandante $demandante
     * @return array
     */
    private function getStats($demandante)
    {
        return [
            // Activas: No finalizadas, no retiradas y NO adjudicadas
            'activas' => $demandante->ofertas()
                ->where('ofertas.estado_id', 1)
                ->wherePivotNotIn('estado_candidato_id', [6, 8, 7])
                ->wherePivotNotIn('proceso_id', [3, 2])
                ->count(),

            // Conseguidas: Solo adjudicadas (Proceso 7)
            'conseguidas' => $demandante->ofertas()
                ->wherePivot('proceso_id', 3)
                ->count(),

            'retiradas' => $demandante->ofertas()
                ->wherePivot('estado_candidato_id', 8)
                ->count(),

            'finalizadas' => $demandante->ofertas()
                ->where(function ($q) {
                    $q->where('demandante_oferta.estado_candidato_id', 6)
                        ->orWhere('ofertas.estado_id', 2);
                })
                ->where(function ($q) {
                    $q->where('demandante_oferta.estado_candidato_id', 6)
                        ->orWhere('ofertas.estado_id', 2);
                })
                ->wherePivotNotIn('estado_candidato_id', [7, 8]) 
                ->wherePivotNotIn('proceso_id', [3, 1])             
                ->count(),
        ]; //                    

    }
    /**
     * @OA\Get(
     * path="/api/ofertas/{oferta}/candidatos",
     * summary="Listar candidatos inscritos en una oferta",
     * description="Retorna una lista paginada de demandantes inscritos. Incluye usuarios activos y aquellos adjudicados históricamente.",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="oferta",
     * in="path",
     * required=true,
     * description="ID de la oferta",
     * @OA\Schema(type="integer", example=5)
     * ),
     * @OA\Parameter(
     * name="rows",
     * in="query",
     * required=false,
     * description="Número de filas por página",
     * @OA\Schema(type="integer", example=10)
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="message", type="string", example="Candidatos inscritos recuperados con éxito"),
     * @OA\Property(property="data", type="object", description="Paginación de Laravel")
     * )
     * ),
     * @OA\Response(response=403, description="No autorizado"),
     * @OA\Response(response=500, description="Error interno")
     * )
     */

    public function candidatosInscritos(Request $request, Oferta $oferta)
    {
        try {
            $rows = $request->get('rows', 10);
            // Filtrar demandantes: deben estar activos/validados 
            // O haber sido ya seleccionados (adjudicados) para esta oferta concreta.
            $paginador = $oferta->demandantes()
                ->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                            ->where('validado', true);
                    })
                        ->orWhere('demandante_oferta.proceso_id', 3);
                })
                ->select('demandantes.id', 'demandantes.nombre', 'demandantes.telefono', 'demandantes.experienciaLaboral', 'demandantes.created_at as alta')
                ->withPivot('fecha', 'revisado', 'estado_candidato_id')
                ->orderBy('demandante_oferta.fecha', 'asc') // Especificamos tabla pivot para evitar ambigüedad
                ->paginate($rows);

            // Transformamos los datos del paginador para front
            $paginador->getCollection()->transform(function ($candidato) {
                $candidato->fecha_inscripcion = optional($candidato->pivot)->fecha;
                $candidato->revisado = (bool)($candidato->pivot->revisado ?? false);
                $candidato->estado_candidato_id = $candidato->pivot->estado_candidato_id ?? null;
                unset($candidato->pivot);
                return $candidato;
            });

            return response()->json([

                'message' => 'Candidatos inscritos recuperados con éxito',
                'data' => $paginador // Esto devuelve current_page, total, data, etc.
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/ofertas/{oferta}/candidatos/{demandante}",
     * summary="Detalle completo de un candidato",
     * description="Muestra el perfil detallado. Si el usuario está de baja, solo es visible si fue adjudicado y está dentro del periodo de gracia de 6 meses.",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="demandante", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Perfil del candidato",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="situacion_nombre", type="string"),
     * @OA\Property(property="es_historico", type="boolean", description="Indica si el usuario ya no está activo")
     * )
     * ),
     * @OA\Response(response=403, description="Acceso denegado por falta de requisitos o baja del sistema")
     * )
     */
    public function detalleCandidato(Oferta $oferta, Demandante $demandante)
    {
        try {

            $user = $demandante->user;

            $seguimiento = $demandante->ofertas()->where('oferta_id', $oferta->id)->first();

            //ver si puede verlo
            $inscrito = ($seguimiento !== null);
            $cumpleRequisitos = $demandante->cumpleRequisitos($oferta);

            if (!$inscrito && !$cumpleRequisitos) {
                return response()->json(['message' => 'No tienes permiso para ver este perfil'], 403);
            }
            $esActivo = ($user->status === \App\Enums\UserEstado::ACTIVO);
            $esValidado = (bool)$user->validado;
            /**
             * LÓGICA DE VISIBILIDAD PARA USUARIOS DADOS DE BAJA
             */
            if (!$esActivo || !$esValidado) {
                // Verificamos si el proceso de esta inscripción fue 'adjudicada' (ID 3)
                $esAdjudicado = ($inscrito && $seguimiento->pivot->proceso_id == 3);
                // Periodo de gracia de 6 meses por si alumno al ser seleccionado se dio de baja
                $fechaBaja = $user->fecha_baja ? \Carbon\Carbon::parse($user->fecha_baja) : null;
                $periodoVigente = $fechaBaja && $fechaBaja->copy()->addMonths(6)->isFuture();

                // Si NO se le adjudicó la plaza O ya pasó el tiempo de gracia, bloqueamos
                if (!$esAdjudicado || !$periodoVigente) {
                    return response()->json([
                        'message' => 'Este perfil ya no está disponible por baja del usuario.'
                    ], 403);
                }

                // Si llega aquí es porque fue seleccionado y está en el periodo de 6 meses.
                // Avisamos a la empresa de que el usuario ya no está activo en el portal.
                $candidatoYaInactivo = true;
            }
            //  Verificar si el demandante tiene títulos requeridos por la oferta
            $titulosOfertaIds = $oferta->titulos->pluck('id');

            if ($titulosOfertaIds->isNotEmpty()) {
                // La oferta tiene títulos específicos
                $cumpleRequisito = $demandante->titulos()
                    ->whereIn('titulos.id', $titulosOfertaIds)
                    ->exists();
            } else {
                //la oferrta es por familia solo
                $cumpleRequisito = $demandante->titulos()
                    ->where('familia_id', $oferta->familia_id)
                    ->exists();
            }

            if (!$cumpleRequisito) {
                return response()->json([
                    'message' => 'Acceso denegado: El perfil no coincide con la rama profesional de la oferta.'
                ], 403);
            }


            //  Obtener solo los campos seleccionados del demandante
            $candidato = Demandante::where('id', $demandante->id)
                ->select('id', 'nombre', 'telefono', 'experienciaLaboral', 'created_at as alta')
                ->with([
                    'direccion',
                    'titulos:id,nombre'
                ])
                ->first();
            //  RECUPERAR DATOS DEL SEGUIMIENTO (PIVOT)
            // Buscamos la relación específica para esta oferta
            $seguimiento = $demandante->ofertas()
                ->where('oferta_id', $oferta->id)
                ->first();

            if ($seguimiento) {
                $candidato->revisado = (bool)$seguimiento->pivot->revisado;
                $candidato->estado_candidato_id = $seguimiento->pivot->estado_candidato_id;
                $candidato->notas_reclutador = $seguimiento->pivot->notas_reclutador;
                $candidato->fecha_inscripcion = $seguimiento->pivot->fecha;
            }

            $situacion = Demandante::where('id', $demandante->id)->with('situacion')->first(); // Cargar la relación sin filtrar campos ->first();
            $centro = Demandante::where('id', $demandante->id)->with('centro')->first(); // Cargar la relación sin filtrar campos ->first();

            if ($candidato) {
                //  Limpiar datos innecesarios
                unset($candidato->situacione_id);
                unset($candidato->pivot);

                // Ocultar dirección si no es visible
                if ($candidato->direccion && $candidato->direccion->visible == 0) {
                    unset($candidato->direccion);
                } else {
                    unset($candidato->direccion->visible, $candidato->direccion->created_at, $candidato->direccion->updated_at);
                }
                $candidato->situacion = $situacion->situacion->situacion;
                $candidato->centro = $centro->centro->nombre;

                // Reformatear la información de los títulos
                $candidato->infoTitulos = $candidato->titulos->map(function ($titulo) {
                    return [

                        'nombre' => $titulo->nombre,
                        'estado' => $titulo->pivot->cursando == 0 ? 'finalizado' : 'en curso',
                        'anio' => $titulo->pivot->año,
                        'centro' => $titulo->pivot->centro
                    ];
                });

                unset($candidato->titulos);
                unset($candidato->centro_id);
                unset($candidato->user_id);


                $candidato->es_historico = isset($candidatoYaInactivo);
                return response()->json([
                    'message' => 'Datos del candidato',
                    'data' => $candidato
                ], 200);
            }

            return response()->json([
                'message' => 'El demandante no se encontró.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/ofertas/{oferta}/noInscritos",
     * summary="Candidatos elegibles no inscritos",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=6)),
     * @OA\Response(
     * response=200,
     * description="Lista de candidatos sugeridos",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="data", type="object", description="Paginación de candidatos")
     * )
     * ),
     * @OA\Response(response=500, description="Error de servidor")
     * )
     */

    public function candidatosNoInscritos(Request $request, Oferta $oferta)
    {
        try {
            $perPage = $request->get('per_page', 6);
            $candidatos = Demandante::query()
                // Solo usuarios que pueden trabajar (Activos y Validados por administración)
                ->whereHas('user', function ($q) {
                    $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                        ->where('validado', true);
                })
                // Filtro dinámico: usa el Scope 'cumpleRequisitos' definido en el modelo Demandante
                ->cumpleRequisitos($oferta)
                // Excluimos a los que ya están en el proceso de esta oferta
                ->whereDoesntHave('ofertas', function ($q) use ($oferta) {
                    $q->where('ofertas.id', $oferta->id);
                })
                ->select('id', 'nombre')
                // Cambiamos get() por paginate()
                ->paginate($perPage);

            return response()->json([
                'message' => 'Candidatos sugeridos cargados correctamente',
                'data' => $candidatos // Laravel devolverá aquí el objeto con current_page, data, total, etc.
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Post(
     * path="/api/ofertas/{oferta}/candidatos/{demandante}/inscribir",
     * summary="Inscribir candidato manualmente",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="demandante", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=201, description="Inscrito con éxito"),
     * @OA\Response(response=403, description="No cumple requisitos de titulación"),
     * @OA\Response(response=409, description="Ya está inscrito"),
     * @OA\Response(response=500, description="Error de servidor")
     * )
     */
    public function añadirCandidato(Oferta $oferta, Demandante $demandante)
    {
        try {
            //  Verificar si está inscrito en la oferta
            $yaInscrito = $demandante->ofertas()->where('oferta_id', $oferta->id)->exists();
            //controlar que demandante tenga titulo que requiere oferta


            if ($yaInscrito) {
                return response()->json([
                    'message' => 'El candidato ya está inscrito en esta oferta.'
                ], 409); // 
            }
            //  Obtener los títulos requeridos para la oferta
            $titulosRequeridos = $oferta->titulos()->pluck('titulo_id');

            if ($titulosRequeridos->isNotEmpty()) {
                // la oferta tiene titulos
                $tieneRequisito = $demandante->titulos()
                    ->whereIn('titulos.id', $titulosRequeridos)
                    ->exists();
                $errorMsg = 'Este candidato no tiene ninguno de los títulos requeridos.';
            } else {
                // la oferta es solo por familia
                // ver si el alumno tiene cualquier título que pertenezca a la familia de la oferta
                $tieneRequisito = $demandante->titulos()
                    ->where('familia_id', $oferta->familia_id)
                    ->exists();
                $errorMsg = 'Este candidato no pertenece a la familia profesional de la oferta: ' . ($oferta->familia->nombre ?? 'N/A');
            }

            if (!$tieneRequisito) {
                return response()->json(['message' => $errorMsg], 403);
            }

            // 3. Inscripción (Attach)
            $demandante->ofertas()->attach($oferta->id, [
                'fecha' => now(),
                'proceso_id' => 1,           // Estado inicial del proceso
                'estado_candidato_id' => 2,  // Estado del candidato (ej: "Enviado por centro")
                'revisado' => true           // Marcamos como revisado ya que lo añade la empresa/gestor
            ]);
            return response()->json([
                'message' => 'Candidato añadido correctamente a la oferta',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Patch(
     * path="/api/ofertas/{oferta}/cerrar",
     * summary="Cierre manual de oferta",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(@OA\Property(property="detalle_motivo_id", type="integer", example=1))
     * ),
     * @OA\Response(response=201, description="Cerrada correctamente"),
     * @OA\Response(response=409, description="Ya estaba cerrada")
     * )
     */
    public function cerrarOferta(Request $request, Oferta $oferta)
    {
        try {
            $request->validate([
                'detalle_motivo_id' => 'required|exists:detalle_motivos,id',
            ]);
            // Evitar procesar una oferta que ya está en estado 'Cerrada' (ID 2)
            if ($oferta->estado_id == 2) {
                return response()->json([
                    'message' => 'La oferta ya está cerrada'
                ], 409);
            }
            $detalle = \App\Models\DetalleMotivo::findOrFail($request->detalle_motivo_id);
            //actualizacion 
            $oferta->forceFill([
                'motivo_id' => 2,
                'detalle_motivo_id' => $detalle->id,
                'estado_id' => 2,
                'fechaCierre' => Carbon::now()->toDateString()
            ])->save();
            $demandantes = $oferta->demandantes;

            foreach ($demandantes as $demandante) {
                // Actualizar el proceso_id en la tabla demandante_oferta
                $oferta->demandantes()->updateExistingPivot($demandante->id, [
                    'proceso_id' => 2 // ID 3 corresponde al estado 'cerrada'
                ]);
            }

            return response()->json([
                'message' => 'oferta cerrada correctamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Patch(
     * path="/api/ofertas/{oferta}/asignar/{demandante}",
     * summary="Adjudicar plaza a candidato",
     * description="Asigna la vacante. Si se cubren todos los puestos, la oferta se cierra automáticamente.",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="demandante", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Candidato asignado con éxito")
     * )
     */
    public function asignarCandidato(Oferta $oferta, Demandante $demandante)
    {
        try {
            if (!$oferta || !$demandante) {
                return response()->json(['message' => 'Oferta o Demandante no encontrado'], 404);
            }

            // Asignar el proceso '3' (adjudicada) al demandante seleccionado
            $oferta->demandantes()->updateExistingPivot($demandante->id, [
                'proceso_id' => 3,
                'estado_candidato_id' => 7
            ]);

            //  Contar cuántos candidatos han sido ya seleccionados (proceso_id = 3)
            $seleccionadosCount = $oferta->demandantes()->wherePivot('proceso_id', 3)->count();

            // Comparar con el número de puestos disponibles (nPuestos)
            if ($seleccionadosCount >= $oferta->nPuestos) {

                // SI SE HAN LLENADO TODAS LAS VACANTES:
                $idDetalleExito = 1; //busca el asignada en detalleMotivo
                // Cambiar a proceso '2' (Cerrada/No seleccionado) a los que sobran
                $oferta->demandantes()
                    ->wherePivot('proceso_id', '!=', 3)
                    ->newPivotStatement() // Accede directamente a la tabla pivote
                    ->where('oferta_id', $oferta->id)
                    ->where('proceso_id', '!=', 3)
                    ->update(['proceso_id' => 2]); // Se mantienen sus estados de seguimiento (visto, entrevista...)

                // Actualizar estado de la oferta
                $oferta->estado_id = 2; // Cerrada
                $oferta->motivo_id = 1; // Asignada/Cubierta
                $oferta->detalle_motivo_id = $idDetalleExito;
                $oferta->fechaCierre = Carbon::now()->toDateString();
                $oferta->save();

                return response()->json([
                    'message' => 'Última vacante cubierta. Oferta cerrada correctamente.',
                    'quedan_vacantes' => false
                ], 200);
            }

            // SI AÚN QUEDAN VACANTES:
            return response()->json([
                'message' => 'Candidato asignado. Aún quedan vacantes disponibles (' . ($oferta->nPuestos - $seleccionadosCount) . ')',
                'quedan_vacantes' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Patch(
     * path="/api/ofertas/{oferta}/candidatos/{demandante}/seguimiento",
     * summary="Gestionar seguimiento de candidato",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="demandante", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\JsonContent(
     * @OA\Property(property="revisado", type="boolean"),
     * @OA\Property(property="estado_candidato_id", type="integer"),
     * @OA\Property(property="notas_reclutador", type="string", maxLength=1000)
     * )
     * ),
     * @OA\Response(response=200, description="Seguimiento actualizado"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function actualizarSeguimiento(Request $request, Oferta $oferta, Demandante $demandante)
    {
        try {
            // Validamos que los datos que llegan son correctos
            $request->validate([
                'estado_candidato_id' => 'nullable|exists:estado_candidatos,id',
                'revisado'            => 'nullable|boolean',
                'notas_reclutador'    => 'nullable|string|max:1000'
            ]);

            // Preparamos los datos a actualizar (solo los que vengan en el request)
            $datosUpdate = [];
            if ($request->has('estado_candidato_id')) $datosUpdate['estado_candidato_id'] = $request->estado_candidato_id;
            if ($request->has('revisado'))            $datosUpdate['revisado'] = $request->revisado;
            if ($request->has('notas_reclutador'))    $datosUpdate['notas_reclutador'] = $request->notas_reclutador;

            // Actualizamos la tabla pivote
            $oferta->demandantes()->updateExistingPivot($demandante->id, $datosUpdate);

            return response()->json([
                'message' => 'Seguimiento actualizado correctamente',
                'data'    => $datosUpdate
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/ofertas/estados-candidatos",
     * summary="Listar estados posibles de candidatos",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200, 
     * description="Lista de estados",
     * @OA\JsonContent(type="array", @OA\Items(type="object"))
     * )
     * )
     */
    public function getEstadosCandidato()
    {
        try {
            // Obtenemos los estados de la tabla que creamos en el Seeder
            $estados = DB::table('estado_candidatos')
                ->select('id', 'nombre')
                ->get();

            return response()->json([
                'message' => 'Datos obtenidos correctamente',
                'data' => $estados
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
