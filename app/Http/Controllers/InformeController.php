<?php

namespace App\Http\Controllers;

use App\Enums\UserEstado;
use App\Models\Demandante;
use App\Models\Empresa;
use App\Models\Oferta;
use App\Models\Titulo;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
/**
 * @OA\Tag(name="Informes", description="Estadísticas y reportes del sistema para el Dashboard de Admin")
 */
class InformeController extends Controller
{
    
   /**
     * @OA\Get(
     * path="/api/informes/resumen",
     * summary="Resumen general del Dashboard (Admin)",
     * description="Obtiene todos los contadores principales en una sola petición para optimizar la carga del frontend.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Resumen de estadísticas recuperado",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="abiertas", type="integer", example=15),
     * @OA\Property(property="cerradas", type="integer", example=40),
     * @OA\Property(property="demandantes", type="integer", example=120),
     * @OA\Property(property="contrataciones", type="integer", example=25)
     * )
     * )
     * )
     * )
     */
    public function ofertasAsignadas()
    {
        try {
            // Contar las inscripciones en demandante_oferta con proceso_id = 3
            $totalAsignadas = DB::table('demandante_oferta')
                ->where('proceso_id', 3)
                ->count();

            return response()->json([
                'data' => $totalAsignadas, // Envolver el resultado en data
                'message' => 'Total de ofertas asignadas recuperado'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }


/**
     * @OA\Get(
     * path="/api/informes/ofertasCerradas",
     * summary="Listado de ofertas finalizadas",
     * description="Devuelve el total y el desglose de ofertas con estado cerrado, incluyendo datos de la empresa.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Informe de históricas generado")
     * )
     */
    public function ofertasCerradas()
    {
        try {
            // Obtener todas las ofertas con estado 'cerrada'
            $ofertas = Oferta::where('estado_id', 2)
                ->with('empresa')
                ->get();

            return response()->json([
                'data' => [
                    'total' => $ofertas->count(),
                    'listado' => $ofertas
                ],
                'message' => 'Informe de ofertas cerradas generado correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
   /**
     * @OA\Get(
     * path="/api/informes/ofertasAbiertas",
     * summary="Monitoreo de ofertas activas",
     * description="Lista todas las ofertas que actualmente están recibiendo candidatos.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Lista de ofertas activas recuperada")
     * )
     */
    public function ofertasAbiertas()
    {
        try {


            // Obtener todas las ofertas con estado 'abierta'
            $ofertas = Oferta::where('estado_id', 1)
                ->with('empresa')
                ->get();

            return response()->json([
                'data' => [
                    'total' => $ofertas->count(),
                    'listado' => $ofertas
                ],
                'message' => 'Informe de ofertas abiertas generado con éxito'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
   /**
     * @OA\Get(
     * path="/api/informes/totalDemandantes",
     * summary="Volumen total de usuarios alumnos",
     * description="Conteo simple de todos los demandantes registrados en el sistema.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Cifra total de demandantes obtenida")
     * )
     */
    public function totalDemandantes()
    {
        try {


            $totalDemandantes = Demandante::count();

            return response()->json([
                'data' => $totalDemandantes, // Mandar el número directamente en data
                'message' => 'Conteo de demandantes recuperado'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
  /**
     * @OA\Get(
     * path="/api/informes/totalEmpresas",
     * summary="Listado y conteo de empresas",
     * description="Devuelve el total de empresas registradas y un listado básico con sus nombres.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Estadísticas de empresas recuperadas")
     * )
     */
    public function totalEmpresas()
    {
        try {


            $totalEmpresas = Empresa::count();
            $empresas = Empresa::select('id', 'nombre')->get();
            return response()->json([
                'data' => [
                    'total' => $totalEmpresas,
                    'listado' => $empresas
                ],
                'message' => 'Estadísticas de empresas recuperadas correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     * path="/api/informes/titulosEstado",
     * summary="Salud del catálogo académico",
     * description="Muestra el balance entre títulos activos e inactivos en el sistema.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Estado del catálogo recuperado")
     * )
     */
    public function titulosEstado()
    {
        try {


            $titulosActivos = Titulo::where('activado', 1)->count(); // Estado activo
            $titulosInactivos = Titulo::where('activado', 0)->count(); // Estado inactivo

            return response()->json([
                'data' => [
                    'totalActivos' => $titulosActivos,
                    'totalInactivos' => $titulosInactivos,
                    'listado' => Titulo::select('id', 'nombre', 'activado')->get()
                ],
                'message' => 'Estado del catálogo académico recuperado'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
   /**
     * @OA\Get(
     * path="/api/informes/empresasSinOfertas",
     * summary="Detectar empresas inactivas",
     * description="Lista empresas que se registraron pero nunca publicaron una oferta. Incluye email para contacto.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Informe de empresas sin actividad generado")
     * )
     */
    public function empresasSinOfertas()
    {
        try {


            $empresas = Empresa::doesntHave('ofertas')
                ->with('user:id,email')
                ->get();

            return response()->json([
                'data' => [
                    'total' => $empresas->count(),
                    'listado' => $empresas
                ],
                'message' => 'Informe de empresas sin actividad generado correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
   /**
     * @OA\Get(
     * path="/api/informes/ofertasSinPostulantes",
     * summary="Ofertas con 0 candidatos",
     * description="Identifica ofertas que no han recibido inscripciones, útil para revisar requisitos demasiado exigentes.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Lista de ofertas sin éxito de convocatoria")
     * )
     */
    public function ofertasSinPostulantes()
    {
        try {


            $ofertas = Oferta::doesntHave('demandantes')
                ->with('empresa:id,nombre') // Solo lo mínimo
                ->select('id', 'nombre', 'empresa_id', 'created_at') // Filtrar columnas pesadas
                ->get();

            return response()->json([
                'data' => [
                    'total' => $ofertas->count(),
                    'listado' => $ofertas
                ],
                'message' => 'Informe de ofertas sin candidatos generado correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
  /**
     * @OA\Get(
     * path="/api/admin/empresa/{id}",
     * summary="Detalle completo de una empresa (Admin)",
     * description="Recupera toda la información de una empresa, incluyendo dirección y datos de usuario.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle de empresa recuperado"),
     * @OA\Response(response=404, description="Empresa no encontrada")
     * )
     */
    public function detalleEmpresaAdmin($id)
    {
        try {
            // Buscar empresa por ID con sus relaciones
            $empresa = Empresa::with(['direccion', 'user'])->find($id);

            if (!$empresa) {
                return response()->json(['message' => 'Empresa no encontrada'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $empresa
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle de la empresa',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }
  /**
     * @OA\Get(
     * path="/api/admin/oferta/{id}",
     * summary="Consultar detalle de una oferta (Admin)",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Detalle de oferta recuperado")
     * )
     */
    public function detalleOfertaAdmin($id)
    {
        try {
            // Buscar oferta con su relación mínima de empresa
            $oferta = Oferta::with('empresa:id,nombre')->find($id);

            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'La oferta técnica solicitada no existe o ha sido eliminada.'
                ], 404);
            }

            return response()->json([
                'data' => $oferta,
                'message' => 'Detalle oferta cargada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Error interno al obtener el detalle de la oferta.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
/**
     * @OA\Get(
     * path="/api/admin/alumnos",
     * summary="Listado avanzado de alumnos (Admin)",
     * description="Permite buscar alumnos por nombre, email, teléfono o título académico. Incluye paginación.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="busqueda", in="query", required=false, description="Texto a buscar", @OA\Schema(type="string")),
     * @OA\Parameter(name="rows", in="query", required=false, description="Filas por página", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Listado de alumnos procesado")
     * )
     */
    public function getAllAlumnos(Request $request)
    {
        try {
            $rows = $request->input('rows', 10);
            $busqueda=$request->input('busqueda');
            $query=Demandante::query();
         $query->whereHas('user', function ($q) {
            $q->where('status', UserEstado::ACTIVO->value)
              ->where('validado', true);
        });

        //  Filtra  búsqueda (Si existe el parámetro search)
        $query->when($busqueda, function ($q) use ($busqueda) {
            $q->where(function ($inner) use ($busqueda) {
                $inner->where('nombre', 'LIKE', "%{$busqueda}%")
              
                      ->orWhere('telefono', 'LIKE', "%{$busqueda}%")
                      // Búsqueda por Email (está en la relación user)
                      ->orWhereHas('user', function ($userQ) use ($busqueda) {
                          $userQ->where('email', 'LIKE', "%{$busqueda}%");
                      })
                      // Búsqueda por Título (está en la relación titulos)
                      ->orWhereHas('titulos', function ($tituloQ) use ($busqueda) {
                          $tituloQ->where('nombre', 'LIKE', "%{$busqueda}%");
                      });
            });
        });

        // Paginación y relaciones
        $alumnos = $query->with(['titulos', 'user'])->paginate($rows);
        $alumnos->through(function ($alumno) {
            return [
                'id'        => $alumno->id,
                'user_id'   => $alumno->user_id,
                'nombre'    => $alumno->nombre, 
                'email'     => $alumno->user->email ?? 'N/A',
                'telefono'  => $alumno->telefono,
                // Mapeo los títulos para enviar solo el nombre y datos del pivot
                'titulos'   => $alumno->titulos->map(function ($titulo) {
                    return [
                        'id'     => $titulo->id,
                        'nombre' => $titulo->nombre,
                        'centro' => $titulo->pivot->centro ?? '',
                        'año'    => $titulo->pivot->año ?? '',
                    ];
                }),
            ];
        });
        return response()->json([
            'message' => 'Alumnos dados de alta obtenidos correctamente',
            'data' => $alumnos 
        ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/admin/empresas",
     * summary="Listado avanzado de empresas (Admin)",
     * description="Retorna empresas validadas y activas. Permite búsqueda por nombre, CIF, email o teléfono (ignorando espacios).",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="busqueda", in="query", required=false, description="Texto a buscar (Nombre, CIF, Email, Teléfono)", @OA\Schema(type="string")),
     * @OA\Parameter(name="rows", in="query", required=false, description="Cantidad de registros por página", @OA\Schema(type="integer", default=10)),
     * @OA\Response(response=200, description="Listado de empresas paginado"),
     * @OA\Response(response=500, description="Error de servidor (Logueado internamente)")
     * )
     */
    public function getAllEmpresas(Request $request)
    {
        try {
            $rows = $request->input('rows', 10);
            $busqueda=$request->input('busqueda');
            $empresas = Empresa::whereHas('user', function ($query) {
               $query->where('status', UserEstado::ACTIVO->value)
                  ->where('validado', true);
            })
          ->when($busqueda, function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('nombre', 'LIKE', "%{$busqueda}%")
                      ->orWhere('cif', 'LIKE', "%{$busqueda}%")
                      ->orWhereRaw("REPLACE(telefono_contacto, ' ', '') LIKE ?", ["%" . str_replace(' ', '', $busqueda) . "%"])
      ->orWhere('telefono_contacto', 'like', "%$busqueda%")
                      ->orWhereHas('user', function ($u) use ($busqueda) {
                          $u->where('email', 'LIKE', "%{$busqueda}%");
                      });
                });
            })
            ->with('user')
            ->paginate($rows);
            $empresas->through(function ($empresa) {
            return [
                'id'       => $empresa->id,
                'nombre'   => $empresa->nombre,
                'email'    => $empresa->user?->email, // Extraemos el mail de la relación
                'cif'      => $empresa->cif,
                'telefono' => $empresa->telefono_contacto, // Ajusta al nombre real de tu columna
            ];
        });
        return response()->json([
            'message' => 'Empresas dadas de alta obtenidas correctamente',
            'data' => $empresas
        ]);
        } catch (\Exception $e) {
            Log::error("Error en el dashboard: " . $e->getMessage());
            return response()->json(['message' => 'Error al conectar con la API, intentelo mas tarde']);
        }
    }
/**
     * @OA\Get(
     * path="/api/admin/alumno/expediente/{id}",
     * summary="Ver expediente detallado del alumno (Admin)",
     * description="Obtiene toda la información académica y de contacto de un alumno específico.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Expediente cargado con éxito"),
     * @OA\Response(response=404, description="Alumno no encontrado")
     * )
     */
    public function getDetalleAlumnoAdmin($id)
    {
        try {
            // Buscar el demandante con todas sus relaciones necesarias
            $alumno = Demandante::with([
                'user:id,email',
                'titulos' => function ($query) {
                    // qeu tabla por ID: 'titulos.id'
                    $query->select('titulos.id', 'titulos.nombre', 'titulos.nivele_id')
                        ->with('nivel:id,nivel');
                }
            ])->find($id);
            if (!$alumno) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alumno no encontrado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $alumno,
                'message' => 'Detalle del alumno cargado correctamente'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el expediente del alumno.',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }
/**
     * @OA\Get(
     * path="/api/informes/especiales/{tipo}",
     * summary="Generar reportes avanzados para exportación",
     * description="Obtiene conjuntos de datos complejos según el tipo solicitado. Tipos disponibles: ALU_FULL, EMP_INACTIVAS, OFE_VACIAS, OFE_HISTORICO, ALU_TITULACION, OFE_EXITO, BRECHA_TALENTO, LEAD_TIME.",
     * tags={"Informes"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="tipo",
     * in="path",
     * required=true,
     * description="Código del reporte a generar",
     * @OA\Schema(type="string", enum={"ALU_FULL", "EMP_INACTIVAS", "OFE_VACIAS", "OFE_HISTORICO", "ALU_TITULACION", "OFE_EXITO", "BRECHA_TALENTO", "LEAD_TIME"})
     * ),
     * @OA\Response(response=200, description="Datos del reporte listos para procesar"),
     * @OA\Response(response=400, description="Tipo de reporte no válido"),
     * @OA\Response(response=500, description="Error en el procesamiento de datos complejos")
     * )
     */
        public function getReportesEspeciales($tipo)
    {
        try {
            $data = null;
            $message = "";

            switch ($tipo) {
                case 'ALU_FULL':
                    // Informe completo de alumnos con sus títulos
                    $data = Demandante::whereHas('user', function ($q) {
                        $q->where('validado', 1);
                    })
                        ->with(['titulos', 'user:id,email'])
                        ->get()
                        ->map(function ($alumno) {
                            return [
                                'nombre' => $alumno->nombre . ' ' . $alumno->apellido,
                                'email' => $alumno->user->email ?? 'N/A',
                                'telefono' => $alumno->telefono,
                                'titulos' => $alumno->titulos->pluck('nombre')->toArray(),
                                'created_at' => $alumno->created_at
                            ];
                        });
                    $message = "Expediente completo de alumnos cargado.";
                    break;

                case 'EMP_INACTIVAS':
                    $data = Empresa::whereDoesntHave('ofertas')
                        ->with(['user:id,email', 'direccion'])
                        ->get();
                    $message = "Listado de empresas sin actividad cargado.";
                    break;

                case 'OFE_VACIAS':
                    //  usar estado_id 
                    $data = Oferta::where('estado_id', 1) // 1 = Abierta
                        ->whereDoesntHave('demandantes')
                        ->with(['empresa:id,nombre,localidad'])
                        ->get();
                    $message = "Ofertas sin candidatos recuperadas correctamente.";
                    break;

                case 'OFE_HISTORICO':
                    // usar estado_id
                    $data = Oferta::where('estado_id', 2) // 2 = Cerrada
                        ->with(['empresa:id,nombre'])
                        ->withCount('demandantes') //  demandantes relacion modelos
                        ->get();
                    $message = "Histórico de ofertas cargado con éxito.";
                    break;
                case 'ALU_TITULACION':
                    // Agrupamos alumnos por el nivel 
                    $data = Demandante::whereHas('user', function ($q) {
                        $q->where('validado', 1);
                    })
                        ->with(['titulos.nivel'])
                        ->get()
                        ->map(function ($alumno) {
                            return [
                                'alumno' => $alumno->nombre . ' ' . $alumno->apellido,
                                // Obtenemos el nombre del nivel del primer título que tenga
                                'nivel'  => $alumno->titulos->first()->nivel->nivel ?? 'Sin nivel',
                                'titulo' => $alumno->titulos->first()->nombre ?? 'N/A'
                            ];
                        });
                    $message = "Ranking de alumnos por titulación generado.";
                    break;

                case 'OFE_EXITO':
                    $data = Oferta::where('estado_id', 2)
                        ->with(['empresa:id,nombre', 'demandantes' => function ($q) {
                            $q->where('proceso_id', 3); // Solo los contratados
                        }])
                        ->orderByDesc('updated_at')
                        ->get()
                        ->map(function ($oferta) {
                            // Juntalos nombres de los alumnos adjudicados
                            $adjudicados = $oferta->demandantes->map(function ($d) {
                                return $d->nombre . ' ' . $d->apellido;
                            })->implode(', ');

                            return [
                                'nombre' => $oferta->nombre,
                                'empresa' => $oferta->empresa->nombre ?? 'N/A',
                                'adjudicado_a' => $adjudicados ?: 'SIN ADJUDICAR',
                                'contrataciones' => $oferta->demandantes->count(),
                                'estado' => $oferta->demandantes->count() > 0 ? 'ADJUDICADA' : 'CERRADA',
                                'fecha_cierre' => $oferta->updated_at
                            ];
                        });
                    $message = "Histórico con detalle de adjudicaciones recuperado.";
                    break;
                case 'BRECHA_TALENTO':
                    // Contar alumnos por título 
                    $demanda = DB::table('titulos')
                        ->join('demandante_titulo', 'titulos.id', '=', 'demandante_titulo.titulo_id')
                        ->select('titulos.id', 'titulos.nombre', DB::raw('count(demandante_titulo.demandante_id) as alumnos_count'))
                        ->groupBy('titulos.id', 'titulos.nombre')
                        ->get();

                    // Contar ofertas con pivot 'ofertas_titulos'
                    $ofertas = DB::table('oferta_titulo')
                        ->select('titulo_id', DB::raw('count(oferta_id) as ofertas_count'))
                        ->groupBy('titulo_id')
                        ->get();

                    //  Cruza los datos
                    $data = $demanda->map(function ($d) use ($ofertas) {
                        $o = $ofertas->where('titulo_id', $d->id)->first();
                        $numOfertas = $o ? $o->ofertas_count : 0;

                        return [
                            'titulo' => $d->nombre,
                            'alumnos' => $d->alumnos_count,
                            'ofertas' => $numOfertas,
                            'diferencia' => $d->alumnos_count - $numOfertas
                        ];
                    });

                    $message = "Brecha de talento calculada mediante tabla intermedia.";
                    break;
                case 'LEAD_TIME':
                    $data = Oferta::where('estado_id', 2)
                        ->has('demandantes', '>', 0) // Que tengan contratados
                        ->with('empresa:id,nombre')
                        ->get()
                        ->map(function ($o) {
                            try {
                                
                                $inicio = is_string($o->created_at)
                                    ? Carbon::createFromFormat('d/m/Y', $o->created_at)
                                    : $o->created_at;

                                $fin = is_string($o->updated_at)
                                    ? Carbon::createFromFormat('d/m/Y', $o->updated_at)
                                    : $o->updated_at;
                            } catch (\Exception $e) {
               
                                $inicio = Carbon::parse($o->created_at);
                                $fin = Carbon::parse($o->updated_at);
                            }

                            $dias = $inicio->diffInDays($fin);

                            return [
                                'oferta' => $o->nombre,
                                'empresa' => $o->empresa->nombre ?? 'N/A',
                                'fecha_publicacion' => $inicio->format('d/m/Y'),
                                'fecha_adjudicacion' => $fin->format('d/m/Y'),
                                'dias_transcurridos' => $dias,
                                'eficiencia' => $dias <= 7 ? 'ALTA' : ($dias <= 15 ? 'MEDIA' : 'BAJA')
                            ];
                        });
                    $message = "Informe de tiempos de colocación generado.";
                    break;
                default:
                    return response()->json([
                        'message' => 'El tipo de informe solicitado no es válido: ' . $tipo
                    ], 400);
            }

            return response()->json([
                'data'    => $data,
                'message' => $message
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al generar el reporte especializado.',
                'errors'  => $e->getMessage()
            ], 500);
        }
    }
}
