<?php

namespace App\Http\Controllers;

use App\Models\Demandante;
use App\Models\DemandanteOferta;
use App\Models\Motivo;
use App\Models\Oferta;
use App\Models\Proceso;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            $queUsuario = $user->role_id == 2 ? $user->empresa : $user->demandante;
            if ($user->role_id == 2) {
                $ofertas = Oferta::Select(
                    'ofertas.id',
                    'ofertas.nombre',
                    'ofertas.observacion',
                    'ofertas.tipoContrato',
                    'ofertas.horario',
                    'ofertas.fechaCierre',
                    'ofertas.nPuestos',
                    'ofertas.estado_id',
                    'empresas.nombre as empresa_nombre',
                    'ofertas.created_at'
                )->join('empresas', 'ofertas.empresa_id', '=', 'empresas.id')->where('empresa_id', $queUsuario->id)->orderBy('created_at', 'desc')
                    ->get();     // Ocultar motivo_id si es null


                //map para modificar motivo_id y estado_id con las relaciones creadas y mostrar info en vez de id
                $ofertas = $ofertas->map(function ($oferta) {
                    // Asegurar que el estado está cargado y obtener solo el nombre
                    $oferta->estado_id = $oferta->estado_id == 1 ? 'Abierta' : 'Cerrada';
                    if ($oferta->fechaCierre == null) {
                        unset($oferta->fechaCierre);
                    }


                    return $oferta;
                });

                /*  foreach ($ofertas as $oferta) {
                    $oferta->titulos->each(function ($titulo) {
                        unset($titulo->pivot); // Elimina la propiedad pivot de cada título
                    });
                }*/
            } else if ($user->role_id == 3) {


                $ofertas = Oferta::whereHas('titulos', function ($query) use ($queUsuario) {
                    $query->whereIn('titulo_id', $queUsuario->titulos->pluck('id'));
                })
                    ->select(
                        'ofertas.id',
                        'ofertas.nombre',
                        'ofertas.observacion',
                        'ofertas.tipoContrato',
                        'ofertas.horario',
                        'ofertas.nPuestos',
                        'ofertas.empresa_id',
                        'empresas.nombre as empresa_nombre',
                        'ofertas.created_at'

                    )
                    ->join('empresas', 'ofertas.empresa_id', '=', 'empresas.id') // no me funciona con with asi que join
                    ->where('estado_id', 1)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($oferta) use ($queUsuario) {
                        // Verificar si el demandante ya está inscrito en la oferta
                        $inscrito = $queUsuario->ofertas()->where('oferta_id', $oferta->id)->exists();

                        // Agregar el campo 'inscrito' al array de la oferta
                        $oferta->inscrito = $inscrito;
                        return $oferta;
                    });
            }


            if ($ofertas->isEmpty()) {
                return response()->json([
                    'mensaje' => 'No hay ninguna oferta de trabajo actualmente'
                ], 200);
            } else {
                return response()->json($ofertas, 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
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
        //  return response()->json([$oferta]);
        try {
            $user = Auth::user();

            $queUsuario = $user->role_id == 2 ? $user->empresa : $user->demandante;

            if ($user->role_id == 2) {
           
            } else if ($user->role_id == 3) {
              
                // Verificar si el demandante cumple los requisitos de titulación para ver la oferta
                $cumpleRequisitos = $oferta->titulos()->whereIn('titulo_id', $queUsuario->titulos->pluck('id'))->exists();

                if (!$cumpleRequisitos) {
                    return response()->json([
                        'mensaje' => 'Este candidato no tiene ninguno de los títulos requeridos para esta oferta.'
                    ], 409);
                }
                $inscripcion = $oferta->demandantes()
                    ->where('demandante_id', $queUsuario->id)
                    ->first();


                $fechaInscripcion = $inscripcion->pivot->fecha;
                $estadoProceso = $inscripcion->pivot->proceso_id;
                //no consigo cargar relación asi que hago consulta
                $proceso = Proceso::find($estadoProceso);

            
            }
            $inscritosCount = $oferta->demandantes()->count();
            $ofertaInfo = Oferta::where('id', $oferta->id)
                ->select(
                    'ofertas.id',
                    'ofertas.nombre',
                    'ofertas.observacion',
                    'ofertas.tipoContrato',
                    'ofertas.horario',
                    'ofertas.nPuestos',
                    'ofertas.estado_id',
                    'ofertas.created_at',
                    'ofertas.updated_at'
                )->first();
            $ofertaInfo = Oferta::find($oferta->id)->load('empresa', 'titulos:nombre,nivele_id', 'titulos.nivel:id,nivel', 'motivo', 'estado');
            $titulosConNivel = $ofertaInfo->titulos->map(function ($titulo) {
                return [
                    'nombre' => $titulo->nombre,
                    'nivele_id' => $titulo->nivele_id,
                    'nivel' => $titulo->nivel->nivel ?? 'Sin nivel'
                ];
            });
            $fechaCierre = $ofertaInfo->estado->tipo === 'cerrada' ? $ofertaInfo->updated_at : null;
            unset($ofertaInfo->estado);
            // unset($ofertaInfo->estado->created_at);
            // unset($ofertaInfo->estado->updated_at);

            //  return response()->json($ofertaInfo,200);
            $response = [
                'id' => $ofertaInfo->id,
                'estado' => $ofertaInfo->estado->tipo,
                'fechaCierre' => $fechaCierre,
                'empresa' => $ofertaInfo->empresa->nombre,
                'motivo' => $ofertaInfo->motivo->tipo ?? 'Sin motivo',
                'titulos' => $titulosConNivel,
                'demandantesInscritos' => $inscritosCount,

            ];
            //dar el id del candidato si se ha asignado la oferta
            if($user->role_id ==2){
                $response['candidatoAsignado'] = ($oferta->estado_id == 2 && $oferta->motivo_id == 1) 
                ? $oferta->demandantes()->wherePivot('proceso_id', 3)->first()?->id 
                : null;            }
            // Si el usuario es demandante, agregamos la info adicional
            if ($user->role_id == 3) {
                $response['infoDemandante'] = [
                    'fechaInscripcion' => $fechaInscripcion,
                    'estadoProceso' => $proceso->estado
                ];
            }

            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
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
     *             @OA\Property(property="titulo", type="integer", example=1, description="ID del título requerido para el puesto, debe existir en la tabla 'titulos'.")
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
                'titulo' => 'required|array',
                'titulo.*' => 'integer|exists:titulos,id',
            ]);
            $existeOferta = Oferta::where('nombre', $request['nombre'])
                ->where('tipoContrato', $request['tipoContrato'])
                ->where('horario', $request['horario'])
                ->where('nPuestos', $request['nPuestos'])
                ->where('empresa_id', $empresa)
                ->exists();
            if ($existeOferta) {
                return response()->json([
                    'mensaje' => 'Ya existe una oferta con esos datos'
                ], 200);
            }
            $oferta = new Oferta();
            $oferta->nombre = $request['nombre'];
            $oferta->observacion = $request['observacion'];
            $oferta->tipoContrato = $request['tipoContrato'];
            $oferta->horario = $request['horario'];
            $oferta->nPuestos = $request['nPuestos'];
            $oferta->estado_id = 1;
            $oferta->empresa_id = $empresa;

            $oferta->save();
            $oferta->titulos()->attach($request['titulo']);
            return response()->json([
                'mensaje' => 'oferta creada correctamente'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                $e->errors()
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
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
            if ($oferta->estado_id == 1) {

                //vuelvo a verificar que tiene los titulos requeridos a la oferta
                $tituloValido = Oferta::where('id', $oferta->id)
                    ->whereHas('titulos', function ($query) use ($demandante) {
                        $query->whereIn('titulos.id', $demandante->titulos->pluck('id'));
                    })->exists();
                if (!$tituloValido) {
                    return response()->json([
                        'mensaje' => 'No tienes el titulo que requiere la oferta'
                    ], 422);
                }
                /*  $inscripcion=DemandanteOferta::create([
                        'fecha'=>now(),
                        'proceso_id'=>1,
                        'demandante_id'=>$demandante,
                        'oferta_id'=>$oferta->id
                    ]);*/
                $yaInscrito = $demandante->ofertas()->where('oferta_id', $oferta->id)->exists();

                if ($yaInscrito) {
                    return response()->json([
                        'mensaje' => 'Ya estás inscrito en esta oferta'
                    ], 422);
                }
                $demandante->ofertas()->attach($oferta->id, [
                    'fecha' => now(),
                    'proceso_id' => 1
                ]);


                // $inscripcion->save();
                return response()->json([
                    'mensaje' => 'Te has inscrito correctamente a la oferta'
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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

            if (!$demandante->ofertas()->where('oferta_id', $oferta->id)->exists()) {
                return response()->json([
                    'mensaje' => 'No estás inscrito en esta oferta'
                ], 422);
            }
            $demandante->ofertas()->detach($oferta->id);
            return response()->json([
                'mensaje' => 'Te has desapuntado correctamente de la oferta'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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
            $demandante = Auth::user()->demandante;
            // Verificar si el demandante está inscrito en alguna oferta
            if (!$demandante->ofertas()->exists()) {
                return response()->json([
                    'mensaje' => 'No tienes ninguna oferta inscrita'
                ], 422);
            }

            // Obtener ofertas inscritas correctamente
            $ofertas = Oferta::whereHas('demandantes', function ($query) use ($demandante) {
                $query->whereIn('oferta_id', $demandante->ofertas->pluck('id')); // 🔹 Corrección
            })
                ->select(
                    'ofertas.id',
                    'ofertas.nombre',
                    'ofertas.observacion',
                    'ofertas.tipoContrato',
                    'ofertas.horario',
                    'ofertas.nPuestos',
                    'ofertas.empresa_id',
                    'empresas.nombre as empresa_nombre',
                    'ofertas.created_at'
                )
                ->join('empresas', 'ofertas.empresa_id', '=', 'empresas.id') // 🔹 Usamos JOIN correctamente
                ->where('estado_id', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($ofertas, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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
                ->select('demandantes.id', 'demandantes.nombre', 'demandantes.telefono', 'demandantes.experienciaLaboral',  'demandantes.created_at as alta')
                ->withPivot('fecha') //  Accede a fecha de inscripción
                ->orderBy('fecha', 'asc') //  Ordena por fecha  la relación sin duplicados
                ->get()
                ->map(function ($candidato) {
                    $candidato->fecha_inscripcion = optional($candidato->pivot)->fecha; // ✅ Acceder correctamente a la fecha desde pivot

                    unset($candidato->pivot);

                    return $candidato;
                });

            return response()->json($candidatos, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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

            //  Verificar si el demandante tiene títulos requeridos por la oferta
            $tieneTitulo = $demandante->titulos()
                ->whereIn('titulos.id', $oferta->titulos->pluck('id'))
                ->exists();

            if (!$tieneTitulo) {
                return response()->json([
                    'mensaje' => 'Acceso denegado: Este candidato no tiene la titulación requerida para esta oferta.'
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
                        'año' => $titulo->pivot->año,
                        'centro' => $titulo->pivot->centro
                    ];
                });

                unset($candidato->titulos);
                unset($candidato->centro_id);
                unset($candidato->user_id);


                return response()->json($candidato, 200);
            }

            return response()->json([
                'mensaje' => 'El demandante no se encontró.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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
            $candidatos = Demandante::whereHas('titulos', function ($query) use ($oferta) {
                $query->whereIn('titulos.id', $oferta->titulos->pluck('id'));
            })->whereDoesntHave('ofertas', function ($query) use ($oferta) {
                $query->where('ofertas.id', $oferta->id);
            })->get();
            if ($candidatos->isEmpty()) {
                return response()->json([
                    'info' => 'Ningún candidato disponible con la titulación requerida sin inscribir'
                ], 200);
            }
            return response()->json($candidatos, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
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
                    'mensaje' => 'El candidato ya está inscrito en esta oferta.'
                ], 409); // 
            }
            //  Obtener los títulos requeridos para la oferta
            $titulosRequeridos = $oferta->titulos()->pluck('titulo_id');

            //  Verificar si el demandante tiene alguno de esos títulos
            $tieneTitulo = $demandante->titulos()->whereIn('titulo_id', $titulosRequeridos)->exists();

            if (!$tieneTitulo) {
                return response()->json([
                    'mensaje' => 'Este candidato no tiene ninguno de los títulos requeridos para esta oferta.'
                ], 403);
            }
            $demandante->ofertas()->attach($oferta->id, [
                'fecha' => now(),
                'proceso_id' => 1
            ]);

            return response()->json([
                'mensaje' => 'Candidato añadido correctamente a la oferta',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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
     *     @OA\Parameter(
     *         name="motivo",
     *         in="path",
     *         required=true,
     *         description="ID del motivo de cierre",
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
    public function cerrarOferta(Oferta $oferta)
    {
        try {
            if ($oferta->estado_id == 2) { // Suponiendo que '3' significa cerrada
                return response()->json([
                    'mensaje' => 'La oferta ya está cerrada'
                ], 409);
            }

            $oferta->forceFill([
                'motivo_id' => 2,
                'estado_id' => 2,
                'fechaCierre' => Carbon::now()->toDateString()
            ])->save();
            $demandantes = $oferta->demandantes;

            foreach ($demandantes as $demandante) {
                // Actualizar el proceso_id en la tabla demandante_oferta
                $oferta->demandantes()->updateExistingPivot($demandante->id, [
                    'proceso_id' => 2 // Asumiendo que el ID 3 corresponde al estado 'cerrada'
                ]);
            }

            return response()->json([
                'mensaje' => 'oferta cerrada correctamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
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
                return response()->json(['mensaje' => 'Oferta o Demandante no encontrado'], 404);
            }

            // Asignar el proceso '3' (adjudicada) al demandante seleccionado
            $oferta->demandantes()->updateExistingPivot($demandante->id, [
                'proceso_id' => 3
            ]);

            // Cambiar el proceso de los demás demandantes a '2' (cerrada)
            $oferta->demandantes()
                ->where('demandante_id', '!=', $demandante->id)
                ->update(['proceso_id' => 2]);

            $oferta->estado_id = 2; // Estado 'cerrada'
            $oferta->motivo_id = 1; // Motivo 'asignada'
          
            $oferta->save();

            return response()->json(['mensaje' => 'Candidato asignado correctamente y proceso actualizado'], 201);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
}
