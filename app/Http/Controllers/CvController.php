<?php


namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\Demandante;
use App\Models\Oferta;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(name="CV", description="Gestión de archivos de Currículum en PDF")
 */
class CvController extends Controller
{
  /**
     * @OA\Get(
     * path="/api/cv",
     * summary="Ver mi CV (Alumno)",
     * tags={"CV"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Datos del CV recuperados")
     * )
     */
 public function show()
    {
        try {
            $cv = Auth::user()->demandante->cv;
            return response()->json([
            'message'=>'Curriculum descargado correctamente',
            'data' => $cv
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/empresa/cv/{oferta_id}/{demandante_id}",
     * summary="Ver CV de un candidato (Empresa)",
     * description="Solo permite el acceso si el alumno está inscrito o es sugerido para esa oferta específica.",
     * tags={"CV"},
     * security={{"sanctum": {}}},
     * @OA\Parameter(name="oferta_id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="demandante_id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="CV obtenido"),
     * @OA\Response(response=403, description="No autorizado a ver este CV")
     * )
     */
    public function showEmpresa($oferta_id, $demandante_id)
    {
      try {
       $user = Auth::user();
        if (!$user || !$user->empresa) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        
        $empresaId = $user->empresa->id;

        //  Obtener la oferta (con sus títulos cargados para el método CumpleRequisitos)
        $oferta = Oferta::with('titulos')
                        ->where('id', $oferta_id)
                        ->where('empresa_id', $empresaId)
                        ->firstOrFail();

        //  Obtener el demandante
        $demandante = Demandante::findOrFail($demandante_id);

        // Validacr de acceso usando lógica de modelo
      $estaInscrito = DB::table('demandante_oferta')
    ->where('oferta_id', $oferta_id)
    ->where('demandante_id', $demandante_id)
    ->exists();

        // Usar  función del modelo para saber si es un candidato sugerido válido
        $esSugerido = $demandante->CumpleRequisitos($oferta);

        if (!$estaInscrito && !$esSugerido) {
            return response()->json(['message' => 'No tienes permiso para acceder al CV de este candidato'], 403);
        }

        // Buscar el CV
        $cv = Cv::where('demandante_id', $demandante_id)->first();
        
        if (!$cv) {
            return response()->json([
                'message' => 'El candidato no ha subido su currículum en PDF',
                'data' => null
            ], 200); 
        }

        return response()->json([
            'message' => 'Curriculum obtenido correctamente',
            'data' => $cv
        ], 200);

        return response()->json([
            'message'=>'Curriculum descargado correctamente',
            'data' => $cv
            ], 200);

   } catch (\Exception $e) {
        Log::error("Error CV Empresa: " . $e->getMessage());
        return response()->json(['error' => 'Error al procesar la solicitud', 'details' => $e->getMessage()], 500);
    }
    }
   /**
     * @OA\Post(
     * path="/api/cv/upload",
     * summary="Subir o actualizar CV PDF",
     * description="Sube un archivo PDF (máx 2MB). Si ya existe uno, lo reemplaza físicamente.",
     * tags={"CV"},
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="file", type="string", format="binary")
     * )
     * )
     * ),
     * @OA\Response(response=200, description="Archivo guardado exitosamente")
     * )
     */
    public function upload(Request $request)
    {
        // Validación inicial fuera del try para que Laravel maneje automáticamente los errores 
        $request->validate([
            'file' => 'required|mimes:pdf|max:2048', // Solo PDF, máximo 2MB
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = Auth::user();
                $demandante = $user->demandante;

                if (!$request->hasFile('file')) {
                    throw new Exception('No se ha recibido ningún archivo.');
                }

                $file = $request->file('file');
                
                //  Busca si ya existe un registro previo
                $cvExistente = Cv::where('demandante_id', $demandante->id)->first();

                //  Si existe, borra el archivo físico del almacenamiento (disco)
                if ($cvExistente && Storage::disk('public')->exists($cvExistente->url)) {
                    Storage::disk('public')->delete($cvExistente->url);
                }

                // Genera nombre único y guarda el archivo físico
                // Usa timestamp para evitar problemas de caché en el navegador del usuario
                $filename = 'cv_' . $demandante->id . '_' . time() . '.pdf';
                $path = $file->storeAs('cvs', $filename, 'public');

                //  Guarda o actualiza en la Base de Datos
                $cv = Cv::updateOrCreate(
                    ['demandante_id' => $demandante->id],
                    [
                        'nombre' => $file->getClientOriginalName(),
                        'url' => $path,
                    ]
                );

                return response()->json([
                    'message' => 'Currículum subido y actualizado con éxito',
                    'data' => $cv
                ], 200);
            });

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Hubo un problema al procesar la subida del archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/cv",
     * summary="Eliminar CV",
     * tags={"CV"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="CV borrado de la DB y del disco")
     * )
     */
    public function destroy()
    {
        try {
            $user = Auth::user();
            $cv = $user->demandante->cv;

            if (!$cv) {
                return response()->json(['message' => 'No hay ningún currículum para eliminar'], 404);
            }

            //  Borra el archivo físico
            if (Storage::disk('public')->exists($cv->url)) {
                Storage::disk('public')->delete($cv->url);
            }

            //  Borra el registro de la base de datos
            $cv->delete();

            return response()->json([
                'message' => 'Currículum eliminado correctamente del sistema'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al intentar eliminar el archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}