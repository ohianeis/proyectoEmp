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

class CvController extends Controller
{
    /**
     * Obtiene el CV del usuario autenticado.
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
     * Muestra el CV de un alumno específico (Para que la Empresa lo vea)
     * @param int $demandante_id
     */
    public function showEmpresa($oferta_id, $demandante_id)
    {
      try {
       $user = Auth::user();
        if (!$user || !$user->empresa) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        
        $empresaId = $user->empresa->id;

        // 1. Obtener la oferta (con sus títulos cargados para el método CumpleRequisitos)
        $oferta = Oferta::with('titulos')
                        ->where('id', $oferta_id)
                        ->where('empresa_id', $empresaId)
                        ->firstOrFail();

        // 2. Obtener el demandante
        $demandante = Demandante::findOrFail($demandante_id);

        // 3. Validación de acceso usando tu lógica de modelo
      $estaInscrito = DB::table('demandante_oferta')
    ->where('oferta_id', $oferta_id)
    ->where('demandante_id', $demandante_id)
    ->exists();

        // Usamos tu función del modelo para saber si es un candidato sugerido válido
        $esSugerido = $demandante->CumpleRequisitos($oferta);

        if (!$estaInscrito && !$esSugerido) {
            return response()->json(['message' => 'No tienes permiso para acceder al CV de este candidato'], 403);
        }

        // 4. Buscar el CV
        $cv = Cv::where('demandante_id', $demandante_id)->first();
        
        if (!$cv) {
            return response()->json([
                'message' => 'El candidato no ha subido su currículum en PDF',
                'data' => null
            ], 200); // Retornamos 200 pero con data null para que el front lo gestione
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
     * Sube un nuevo CV o reemplaza el existente.
     */
    public function upload(Request $request)
    {
        // Validación inicial fuera del try para que Laravel maneje automáticamente los errores 422
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
                
                // 1. Buscamos si ya existe un registro previo
                $cvExistente = Cv::where('demandante_id', $demandante->id)->first();

                // 2. Si existe, borramos el archivo físico del almacenamiento (disco)
                if ($cvExistente && Storage::disk('public')->exists($cvExistente->url)) {
                    Storage::disk('public')->delete($cvExistente->url);
                }

                // 3. Generamos nombre único y guardamos el archivo físico
                // Usamos timestamp para evitar problemas de caché en el navegador del usuario
                $filename = 'cv_' . $demandante->id . '_' . time() . '.pdf';
                $path = $file->storeAs('cvs', $filename, 'public');

                // 4. Guardamos o actualizamos en la Base de Datos
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
     * Elimina el CV tanto de la DB como del disco.
     */
    public function destroy()
    {
        try {
            $user = Auth::user();
            $cv = $user->demandante->cv;

            if (!$cv) {
                return response()->json(['message' => 'No hay ningún currículum para eliminar'], 404);
            }

            // 1. Borramos el archivo físico
            if (Storage::disk('public')->exists($cv->url)) {
                Storage::disk('public')->delete($cv->url);
            }

            // 2. Borramos el registro de la base de datos
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