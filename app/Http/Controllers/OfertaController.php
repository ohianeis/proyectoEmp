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

class OfertaController extends Controller
{
    //
    /**
     * @OA\Get(
     *     path="/api/ofertas",
     *     summary="Obtener lista de ofertas de trabajo según el tipo de usuario",
     *     description="Devuelve una lista de ofertas de trabajo filtradas según el tipo de usuario: 
     *     - Para demandantes, incluye ofertas relacionadas con sus títulos, solo si el estado de la oferta es 'Abierta' (estado_id = 1).
     *     - Para empresas, muestra todas sus ofertas (tanto abiertas como cerradas).
     *      Ordenadas por fecha de creación descendente.",
     *     tags={"Ofertas"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ofertas obtenida correctamente o mensaje si no hay ofertas disponibles.",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1, description="ID de la oferta."),
     *                         @OA\Property(property="nombre", type="string", example="Desarrollador Web", description="Título de la oferta."),
     *                         @OA\Property(property="observacion", type="string", example="Se busca desarrollador con experiencia en Laravel.", description="Descripción general."),
     *                         @OA\Property(property="tipoContrato", type="string", example="Indefinido", description="Tipo de contrato."),
     *                         @OA\Property(property="horario", type="string", example="8:00 - 16:00", description="Horario laboral."),
     *                         @OA\Property(property="nPuestos", type="integer", example=2, description="Número de vacantes."),
     *                         @OA\Property(property="motivo", type="string", example="sin demandante de la bolsa", description="Motivo de cierre de la oferta."),
     *                         @OA\Property(property="estado", type="string", example="Abierta", description="Estado de la oferta (Abierta/Cerrada)."),
     *                         @OA\Property(property="empresa_id", type="integer", example=5, description="ID de la empresa."),
     *                         @OA\Property(property="empresa_nombre", type="string", example="Tech Solutions S.A.", description="Nombre de la empresa."),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-02T08:00:00Z", description="Fecha de publicación.")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="mensaje", type="string", example="No hay ninguna oferta de trabajo actualmente.")
     *                 )
     *             }
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="No estás autenticado. Por favor, inicia sesión para continuar.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Unauthenticated.")
     *             }
     *         )
     *     ),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener las ofertas de trabajo.")
     *         )
     *     )
     * )
     */

    public function index()
    {
        try {
            $user = Auth::user();
            $queUsuario = ($user->role_id == 2) ? $user->empresa : $user->demandante;

            if ($user->role_id == 2) {
                //datos para empresa
                $ofertasDatos = Oferta::with(['familia']) // Traemos la familia para la tarjeta
                    ->withCount('demandantes')      // Necesario para "X personas inscritas"
                    ->where('empresa_id', $queUsuario->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $ofertas = $ofertasDatos->map(function ($oferta) {
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
            } else if ($user->role_id == 3) {
                // datos enviar perfil alumno

                $misTitulosIds = $queUsuario->titulos->pluck('id')->toArray();
                $misFamiliasIds = $queUsuario->titulos->pluck('familia_id')->unique()->toArray(); //familias que pertenecena sus ttulos

                $ofertas = Oferta::with(['familia', 'empresa'])
                    ->where('estado_id', 1)
                    ->whereHas('empresa.user', function ($q) {
                        $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                            ->where('validado', true);
                    })
                    ->whereDoesntHave('demandantes', fn($q) => $q->where('demandante_id', $queUsuario->id))
                    ->where(function ($query) use ($misTitulosIds, $misFamiliasIds) {
                        $query->whereHas('titulos', fn($q) => $q->whereIn('titulos.id', $misTitulosIds))
                            ->orWhere(fn($q) => $q->whereIn('familia_id', $misFamiliasIds)->whereDoesntHave('titulos'));
                    })
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($oferta) use ($misTitulosIds) {
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
                            'created_at' => $oferta->created_at, // Formateo de fecha opcional
                            'esAnonima' => (bool)$oferta->esAnonima
                        ];
                    });
            }

            return response()->json([
                'message' => $ofertas->isEmpty() ? 'No hay ofertas' : 'Ofertas cargadas',
                'data' => $ofertas
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/ofertas/{oferta}",
     *     summary="Obtener detalles de una oferta de trabajo",
     *     description="Devuelve la información completa de una oferta, validando permisos de empresa o titulación del demandante.",
     *     tags={"Ofertas"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 28|EDpCqsQH14heM01S88StGH7hDIhd4WMALSq9LflU5bd75bd5"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta a consultar.",
     *         @OA\Schema(
     *             type="integer",
     *             example=5
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la oferta obtenidos correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=5),
     *             @OA\Property(property="nombre", type="string", example="Desarrollador Full Stack"),
     *             @OA\Property(property="estado", type="string", example="Abierta"),
     *             @OA\Property(property="empresa", type="string", example="Empresa Tecnológica"),
     *             @OA\Property(property="motivo", type="string", example="Expansión del equipo"),
     *             @OA\Property(property="inscrito", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para consultar esta oferta.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="No eres el propietario de esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="El demandante no tiene los títulos requeridos para ver la oferta.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Este candidato no tiene ninguno de los títulos requeridos para esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Oferta no encontrada.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Recurso no encontrado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener la oferta.")
     *         )
     *     )
     * )
     */
    public function show(Oferta $oferta)
    {
        try {
            $user = Auth::user();
            $queUsuario = ($user->role_id == 2) ? $user->empresa : $user->demandante;

            // Cargar todo 
            $ofertaInfo = Oferta::with(['empresa.direccion', 'titulos.nivel', 'motivo', 'estado', 'familia'])
                ->findOrFail($oferta->id);

            // para control match e inscrito
            $match = 0;
            $inscrito = false;

            // para info demandante
            if ($user->role_id == 3) {

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

                // Cálculo de Match
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
     *     path="/api/ofertas",
     *     summary="Registrar una nueva oferta de trabajo",
     *     description="Crea una nueva oferta de trabajo asociada a la empresa del usuario autenticado.",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "observacion", "tipoContrato", "horario", "nPuestos", "titulo"},
     *             @OA\Property(property="nombre", type="string", maxLength=45, example="Desarrollador Web", description="Nombre de la oferta."),
     *             @OA\Property(property="observacion", type="string", maxLength=2000, example="Se busca desarrollador con experiencia en Laravel.", description="Descripción de la oferta."),
     *             @OA\Property(property="tipoContrato", type="string", maxLength=45, example="Indefinido", description="Tipo de contrato."),
     *             @OA\Property(property="horario", type="string", maxLength=45, example="8:00 - 16:00", description="Horario de trabajo."),
     *             @OA\Property(property="nPuestos", type="integer", example=2, description="Número de vacantes disponibles."),
     *             @OA\Property(property="titulo", type="integer", example={1}, description="ID del título requerido para el puesto, debe existir en la tabla 'titulos'.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Oferta creada correctamente y vinculada con el título.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Oferta creada correctamente."),
     *             @OA\Property(property="id", type="integer", example=10, description="ID de la oferta creada."),
     *             @OA\Property(property="empresa_id", type="integer", example=5, description="ID de la empresa asociada."),
     *             @OA\Property(property="titulo_id", type="integer", example=1, description="ID del título vinculado a la oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Errores de validación.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="object", example={
     *                 "nombre": {"El campo nombre es obligatorio."},
     *                 "observacion": {"El campo observacion es obligatorio."},
     *                 "titulo": {"El título seleccionado no es válido."}
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto: la oferta ya existe.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="mensaje", type="string", example="Título ya existente")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al registrar la oferta.")
     *         )
     *     )
     * )
     */


    public function store(Request $request)
    {


        try {
            $usuario = Auth::user();
            $empresa = $usuario->empresa->id;
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
    //metodos para editar oferta de trabajo
    //controla si hay inscritos ya para ver que datos puede editar la empresa
    public function edit($id)
{
    try{
         $oferta = Oferta::with('titulos:id')->findOrFail($id);
    
    return response()->json([
       'message' => 'Datos cargados correctamente',
            'data' => [
                'oferta' => $oferta,
                'bloqueado' => $oferta->tieneInscritos()
            ]
    ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['errors' => 'La oferta no existe.'], 404);
        
    }catch(Exception $e){
        return response()->json(['errors'=>'Error en la petición de editar'],500);
    }
   
}
public function update(Request $request, $id)
    {
        try {
            $oferta = Oferta::findOrFail($id);
            $bloqueado = $oferta->tieneInscritos();

            // 1. Definimos qué campos se pueden editar SIEMPRE
            $camposPermitidos = ['observacion', 'horario', 'nPuestos', 'incorporacion', 'esAnonima'];

            // 2. Si NO hay inscritos, añadimos los campos críticos
            if (!$bloqueado) {
                array_push($camposPermitidos, 'nombre', 'tipoContrato', 'familia_id');
            }

            // 3. Solo filtramos los campos permitidos
            $data = $request->only($camposPermitidos);
            
            // Actualizamos la tabla principal
            $oferta->update($data);

            // 4. Lógica para los títulos (Muchos a Muchos)
            if (!$bloqueado && $request->has('titulo')) {
                $oferta->titulos()->sync($request->titulo);
            }

            // 5. Respuesta según el estado de bloqueo
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
     * summary="Cambiar el estado de anonimato de una oferta",
     * tags={"Ofertas Empresa"},
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\JsonContent(
     * @OA\Property(property="esAnonima", type="boolean", example=true)
     * )
     * ),
     * @OA\Response(response=200, description="Estado actualizado")
     * )
     */
    public function cambiarAnonimato($id)
    {

        try {
            $usuario = Auth::user();
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
     *     path="/api/ofertas/{oferta}/apuntarse",
     *     summary="Inscribirse en una oferta de trabajo",
     *     description="Permite que un usuario demandante se inscriba en una oferta de trabajo si cumple con los títulos requeridos. Si el usuario no tiene los títulos adecuados, la inscripción será rechazada.",
     *     tags={"Ofertas/Demandante"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta a la que el demandante quiere inscribirse.",
     *         @OA\Schema(
     *             type="integer",
     *             example=3
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscripción realizada correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Te has inscrito correctamente a la oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="El demandante no cumple con los requisitos de la oferta.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="No tienes el título que requiere la oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al inscribirse en la oferta.")
     *         )
     *     )
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
     *     path="/api/ofertas/{oferta}/desapuntarse",
     *     summary="Cancelar inscripción en una oferta de trabajo",
     *     description="Permite que un usuario demandante cancele su inscripción en una oferta de trabajo. Si no está inscrito, devuelve un mensaje de error.",
     *     tags={"Ofertas/Demandante"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta de la que el demandante quiere desapuntarse.",
     *         @OA\Schema(
     *             type="integer",
     *             example=2
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscripción eliminada correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Te has desapuntado correctamente de la oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *   @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="error", type="string", example="Recurso no encontrado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El demandante no estaba inscrito en la oferta.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="No estás inscrito en esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al cancelar la inscripción en la oferta.")
     *         )
     *     )
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
                'fecha' => now() // Opcional: guardar cuándo se desapuntó
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
     *     path="/api/ofertas/inscritas/listado",
     *     summary="Obtener lista de ofertas en las que el demandante está inscrito",
     *     description="Devuelve la lista de ofertas de trabajo en las que un demandante está inscrito, incluyendo detalles de la empresa. 
     *     Si el demandante no está inscrito en ninguna oferta, devuelve un mensaje de error.",
     *     tags={"Ofertas/Demandante"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ofertas en las que el demandante está inscrito.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2, description="ID de la oferta."),
     *                 @OA\Property(property="nombre", type="string", example="Desarrollador Web", description="Título de la oferta."),
     *                 @OA\Property(property="observacion", type="string", example="Experiencia mínima de 2 años.", description="Observaciones adicionales."),
     *                 @OA\Property(property="tipoContrato", type="string", example="Indefinido", description="Tipo de contrato."),
     *                 @OA\Property(property="horario", type="string", example="9:00 - 17:00", description="Horario de trabajo."),
     *                 @OA\Property(property="nPuestos", type="integer", example=3, description="Número de puestos disponibles."),
     *                 @OA\Property(property="empresa_id", type="integer", example=5, description="ID de la empresa."),
     *                 @OA\Property(property="empresa_nombre", type="string", example="Tech Solutions S.A.", description="Nombre de la empresa."),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-02T08:00:00Z", description="Fecha de creación de la oferta.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *   @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El demandante no tiene ofertas inscritas.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="No tienes ninguna oferta inscrita.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener la lista de ofertas inscritas.")
     *         )
     *     )
     * )
     */

    public function ofertasInscritas()
    {
        try {
            $user = Auth::user();
            $demandante = $user->demandante;
            // 1. Cargamos titulos candidato para hacer % match con titulos requeridos en oferta
            $misTitulosIds = $demandante->titulos->pluck('id')->toArray();
            $ofertas = $demandante->ofertas()
                ->with(['empresa.direccion', 'estado', 'titulos'])
                ->withCount('demandantes')
                ->orderBy('demandante_oferta.fecha', 'desc')
                ->get();

            if ($ofertas->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tienes ninguna oferta inscrita',
                    'data' => []
                ], 200);
            }

            $data = $ofertas->map(function ($oferta) use ($misTitulosIds, $demandante) {
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
            });

            return response()->json([
                'message' => 'Ofertas recuperadas correctamente',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/ofertas/{oferta}/candidatos",
     *     summary="Obtener lista de candidatos inscritos en una oferta",
     *     description="Devuelve la lista de demandantes inscritos en una oferta específica.",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta de la cual se desean obtener los candidatos inscritos.",
     *         @OA\Schema(
     *             type="integer",
     *             example=5
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de candidatos inscritos en la oferta.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=12, description="ID del demandante."),
     *                 @OA\Property(property="nombre", type="string", example="Juan Pérez", description="Nombre completo del demandante."),
     *                 @OA\Property(property="email", type="string", example="juan.perez@example.com", description="Correo electrónico del demandante."),
     *                 @OA\Property(property="telefono", type="string", example="+34 600 123 456", description="Teléfono de contacto del demandante.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *   @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="Usuario no autorizado.")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener la lista de candidatos.")
     *         )
     *     )
     * )
     */

    public function candidatosInscritos(Oferta $oferta)
    {
        try {
            $candidatos = $oferta->demandantes()
                //alumno sea activo y validado o estado oferta 3 que es adjudicada
                ->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                            ->where('validado', true);
                    })
                        ->orWhere('demandante_oferta.proceso_id', 3);
                })
                ->select('demandantes.id', 'demandantes.nombre', 'demandantes.telefono', 'demandantes.experienciaLaboral',  'demandantes.created_at as alta')
                ->withPivot('fecha', 'revisado', 'estado_candidato_id') //  Accede a fecha de inscripción
                ->orderBy('fecha', 'asc') //  Ordena por fecha  la relación sin duplicados
                ->get()
                ->map(function ($candidato) {
                    $candidato->fecha_inscripcion = optional($candidato->pivot)->fecha; // ✅ Acceder correctamente a la fecha desde pivot
                    $candidato->revisado = (bool)$candidato->pivot->revisado;
                    $candidato->estado_candidato_id = $candidato->pivot->estado_candidato_id;
                    unset($candidato->pivot);

                    return $candidato;
                });

            return response()->json([
                'message' => 'Candidatos incritos recuperados con éxito',
                'data' => $candidatos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/ofertas/{oferta}/candidatos/{demandante}",
     *     summary="Obtener detalles de un candidato vinculado a una oferta",
     *     description="Devuelve la información detallada de un demandante que cumple los requisitos de titulación para una oferta específica.",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta para validar la elegibilidad del candidato.",
     *         @OA\Schema(
     *             type="integer",
     *             example=3
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="demandante",
     *         in="path",
     *         required=true,
     *         description="ID del demandante cuyo detalle se desea obtener.",
     *         @OA\Schema(
     *             type="integer",
     *             example=12
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información detallada del demandante.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=12, description="ID del demandante."),
     *             @OA\Property(property="nombre", type="string", example="Juan Pérez", description="Nombre completo."),
     *             @OA\Property(property="telefono", type="string", example="+34 600 123 456", description="Teléfono de contacto."),
     *             @OA\Property(property="experienciaLaboral", type="string", example="5 años en desarrollo web", description="Experiencia laboral."),
     *             @OA\Property(property="situacion_nombre", type="string", example="Desempleado", description="Situación laboral."),
     *             @OA\Property(property="centro_nombre", type="string", example="Universidad de Madrid", description="Centro educativo."),
     *             @OA\Property(
     *                 property="direccion",
     *                 type="object",
     *                 description="Dirección del demandante (si es visible).",
     *                 @OA\Property(property="calle", type="string", example="Calle Mayor 15"),
     *                 @OA\Property(property="ciudad", type="string", example="Madrid"),
     *                 @OA\Property(property="codigo_postal", type="string", example="28013")
     *             ),
     *             @OA\Property(
     *                 property="infoTitulos",
     *                 type="array",
     *                 description="Lista de títulos del demandante.",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="titulo_id", type="integer", example=1, description="ID del título."),
     *                     @OA\Property(property="nombre", type="string", example="Ingeniería Informática", description="Nombre del título."),
     *                     @OA\Property(property="estado", type="string", example="finalizado", description="Estado del curso."),
     *                     @OA\Property(property="año", type="integer", example=2021, description="Año de finalización."),
     *                     @OA\Property(property="centro", type="string", example="Universidad Politécnica", description="Centro educativo donde se cursó.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Este candidato no tiene la titulación requerida para esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El demandante no existe.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="El demandante no se encontró.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener el detalle del candidato.")
     *         )
     *     )
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
     *     path="/api/ofertas/{oferta}/noInscritos",
     *     summary="Obtener lista de candidatos que cumplen los requisitos pero no están inscritos",
     *     description="Devuelve la lista de demandantes que tienen títulos relacionados con la oferta, pero no están inscritos en ella.",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta para la que se buscan candidatos elegibles no inscritos.",
     *         @OA\Schema(
     *             type="integer",
     *             example=3
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de candidatos no inscritos en la oferta.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=15, description="ID del demandante."),
     *                 @OA\Property(property="nombre", type="string", example="Ana López", description="Nombre completo del demandante."),
     *                 @OA\Property(property="telefono", type="string", example="+34 600 987 321", description="Teléfono de contacto del demandante."),
     *                 @OA\Property(property="experienciaLaboral", type="string", example="3 años en análisis de datos", description="Experiencia laboral."),
     *                 @OA\Property(
     *                     property="titulos",
     *                     type="array",
     *                     description="Lista de títulos que tiene el candidato y que coinciden con los requeridos por la oferta.",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="titulo_id", type="integer", example=2, description="ID del título."),
     *                         @OA\Property(property="nombre", type="string", example="Máster en Big Data", description="Nombre del título.")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener la lista de candidatos no inscritos.")
     *         )
     *     )
     * )
     */

    public function candidatosNoInscritos(Oferta $oferta)
    {
        try {
            $candidatos = Demandante::query()
                ->whereHas('user', function ($q) {
                    $q->where('status', \App\Enums\UserEstado::ACTIVO->value)
                        ->where('validado', true);
                })
                ->cumpleRequisitos($oferta)
                ->whereDoesntHave('ofertas', function ($q) use ($oferta) {
                    $q->where('ofertas.id', $oferta->id);
                })
                ->select('id', 'nombre')
                ->get();

            return response()->json([
                'message' => 'Candidatos sugeridos cargados correctamente',
                'data' => $candidatos
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/ofertas/{oferta}/candidatos/{demandante}/inscribir",
     *     summary="Añadir un candidato a una oferta",
     *     description="Permite inscribir a un demandante en una oferta de empleo, asegurando que no esté previamente inscrito.",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta en la que se inscribirá el candidato.",
     *         @OA\Schema(
     *             type="integer",
     *             example=5
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="demandante",
     *         in="path",
     *         required=true,
     *         description="ID del demandante que se inscribirá en la oferta.",
     *         @OA\Schema(
     *             type="integer",
     *             example=12
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="El candidato ha sido inscrito correctamente en la oferta.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Candidato añadido correctamente a la oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Este candidato no tiene la titulación requerida para esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="El candidato ya estaba inscrito en la oferta.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="El candidato ya está inscrito en esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al inscribir el candidato.")
     *         )
     *     )
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
                // ver si el alumno tiene CUALQUIER título que pertenezca a la familia de la oferta
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
     *     path="/api/ofertas/{oferta}/cerrar",
     *     summary="Cierra una oferta con un motivo específico",
     *     description="Cambia el estado de la oferta y asigna un motivo de cierre.",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         required=true,
     *         description="ID de la oferta a cerrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Oferta cerrada correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Oferta cerrada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Este candidato no tiene la titulación requerida para esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="La oferta ya está cerrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="La oferta ya está cerrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Error inesperado")
     *         )
     *     )
     * )
     */
    public function cerrarOferta(Request $request, Oferta $oferta)
    {
        try {
            $request->validate([
            'detalle_motivo_id' => 'required|exists:detalle_motivos,id',
        ]);
            if ($oferta->estado_id == 2) { // Suponiendo que '3' significa cerrada
                return response()->json([
                    'message' => 'La oferta ya está cerrada'
                ], 409);
            }
    $detalle = \App\Models\DetalleMotivo::findOrFail($request->detalle_motivo_id);
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
     *     path="/api/ofertas/{oferta}/asignar/{demandante}",
     *     summary="Asigna un candidato a una oferta y actualiza el proceso",
     *     description="Este endpoint permite asignar un demandante a una oferta y actualizar el estado de otros demandantes.",
     *     operationId="asignarCandidato",
     *     tags={"Ofertas/Empresa"},
     *     security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token de autenticación en formato Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer 17|n50b7aY4qRRGMhjRyIEMMS5fzmmZapdiyAahoygobe6ca3a3"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="oferta",
     *         in="path",
     *         description="ID de la oferta",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="demandante",
     *         in="path",
     *         description="ID del demandante asignado",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Candidato asignado correctamente y proceso actualizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Candidato asignado correctamente y proceso actualizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado. Es necesario enviar un token válido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado. No tienes permisos para realizar esta acción.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mensaje", type="string", example="Este candidato no tiene la titulación requerida para esta oferta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Recurso no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Error en la asignación del candidato")
     *         )
     *     )
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

            // 2. Contar cuántos candidatos han sido ya seleccionados (proceso_id = 3)
            $seleccionadosCount = $oferta->demandantes()->wherePivot('proceso_id', 3)->count();

            // 3. Comparar con el número de puestos disponibles (nPuestos)
            if ($seleccionadosCount >= $oferta->nPuestos) {

                // SI SE HAN LLENADO TODAS LAS VACANTES:
            $idDetalleExito = 1;//busca el asignada en detalleMotivo
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
     * Actualiza el estado o seguimiento de un candidato en una oferta
     */
    /**
     * @OA\Patch(
     * path="/api/ofertas/{oferta}/candidatos/{demandante}/seguimiento",
     * summary="Actualizar el seguimiento de un candidato",
     * description="Permite marcar como revisado, cambiar el estado del proceso o añadir notas a un candidato específico en una oferta.",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="demandante", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\JsonContent(
     * @OA\Property(property="revisado", type="boolean", example=true),
     * @OA\Property(property="estado_candidato_id", type="integer", example=3),
     * @OA\Property(property="notas_reclutador", type="string", example="Candidato muy interesante para entrevista presencial.")
     * )
     * ),
     * @OA\Response(response=200, description="Actualizado correctamente")
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
     * summary="Obtener lista de estados posibles para un candidato",
     * description="Devuelve los estados (Inscrito, Entrevista, Seleccionado, etc.) definidos en la base de datos.",
     * tags={"Ofertas/Empresa"},
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista de estados obtenida correctamente.",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="nombre", type="string", example="Entrevista")
     * )
     * )
     * ),
     * @OA\Response(response=401, description="No autenticado")
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
