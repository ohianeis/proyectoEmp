<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use \stdClass;

/**
 * @OA\Tag(name="Auth", description="Operaciones de autenticación y registro")
 */
class AuthController extends Controller
{
   /**
     * @OA\Get(
     * path="/api/registro/roles",
     * summary="Roles disponibles para registro",
     * tags={"Auth"},
     * @OA\Response(
     * response=200,
     * description="Lista de roles (excluye Admin)",
     * @OA\JsonContent(type="array", @OA\Items(type="object"))
     * )
     * )
     */
    public function roles()
    {
        try {
            //exlcuir id 1 super Admin
            $tiposRoles = Role::select('id', 'rol')->where('id', '!=', 1)->orderby('id')->get();
            return response()->json($tiposRoles, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
   /**
     * @OA\Post(
     * path="/api/registro",
     * summary="Registro de nuevo usuario",
     * tags={"Auth"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","email","password","role"},
     * @OA\Property(property="name", type="string", example="Usuario Ejemplo"),
     * @OA\Property(property="email", type="string", format="email", example="user@ejemplo.com"),
     * @OA\Property(property="password", type="string", format="password", minLength=6),
     * @OA\Property(property="role", type="integer", description="2 para Empresa, 3 para Alumno", example=3)
     * )
     * ),
     * @OA\Response(response=200, description="Usuario creado y token generado"),
     * @OA\Response(response=409, description="Usuario inactivo (requiere reactivación)"),
     * @OA\Response(response=422, description="Error de validación o email duplicado")
     * )
     */
    public function registro(Request $request)
    {
        // 1. Verifir existencia 
        $usuarioExistente = User::where('email', $request->email)->first();

        if ($usuarioExistente) {
            //  Está inactivo (se dio de baja en el pasado)
            if ($usuarioExistente->status === \App\Enums\UserEstado::INACTIVO->value) {
                return response()->json([
                    'message' => 'Contacte con el centro.'
                ], 409);
            }

            //  Está activo pero intenta registrarse de nuevo
            return response()->json([
                'message' => 'No se puede registrar con ese correo.'
            ], 422);
        }
        $request->validate(
            [
                'name' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|integer|in:2,3|exists:roles,id',
            ],
            [
                'role.exists' => 'El rol seleccionado no es válido',
                'email.email' => 'Por favor, introduce un correo electrónico válido',
                'email.unique' => 'El correo electrónico ya está registrado',
            ]
        );


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'validado' => 0,
            'role_id' => $request->role
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
  /**
     * @OA\Post(
     * path="/api/login",
     * summary="Inicio de sesión",
     * tags={"Auth"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", format="email"),
     * @OA\Property(property="password", type="string", format="password")
     * )
     * ),
     * @OA\Response(response=200, description="Login exitoso con token y rol"),
     * @OA\Response(response=401, description="Credenciales incorrectas"),
     * @OA\Response(response=403, description="Cuenta inactiva o pendiente de validación")
     * )
     */
    public function login(Request $request)
    {
        // Validación de los datos entrantes
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        // Verificar  existencia y contraseña
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'mensaje' => 'Las credenciales introducidas no son correctas.'
            ], 401);
        }
        $statusActual = ($user->status instanceof \BackedEnum) ? $user->status->value : $user->status;
        // Si el usuario está inactivo, no le dejamos entrar aunque la contraseña sea correcta.
        if ($statusActual === \App\Enums\UserEstado::INACTIVO->value) {
            return response()->json([
                'mensaje' => 'Tu cuenta ha sido desactivada. Contacta con el centro si deseas reactivarla.'
            ], 403);
        }
        //  Si el usuario existe y la contraseña es correcta, pero NO ESTÁ VALIDADO
        if (!(bool)$user->validado) {
            return response()->json([
                'mensaje' => 'Tu cuenta aún está pendiente de revisión por el centro.'
            ], 403);
        }
        $user->tokens()->delete(); //borrar tokens anteriores por seguridad y limpieza tabla
       // Asignación de Habilidades (Abilities) según el rol
        $abilities = [];
        switch ($user->role_id) {
            case 1: //administrador
                $abilities = ['administrador'];
                break;
            case 2: //empresa
                $abilities = ['empresa'];
                break;
            case 3: //demandante
                $abilities = ['demandante'];
                break;
        }
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        $responseData = [
            'mensaje'    => true,
            'usuario'    => $user->name,
            'rol'        => strtolower($user->rol->rol),
            'token'      => $token,
            'token_type' => 'Bearer',
        ];

        if($user->role_id===1){
            $responseData['user']=$user->id;
        }
        if ($user->change_pass) {
            $responseData['change_pass'] = 1;
        }

        return response()->json($responseData);
    }
   /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Cierre de sesión",
     * tags={"Auth"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Token eliminado correctamente")
     * )
     */
    public function logout(Request $request)
    {
        try {
            // Borrar el token que el usuario está usando en esta petición
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sesión cerrada con éxito'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al cerrar sesión'
            ], 500);
        }
    }
   /**
     * @OA\Get(
     * path="/api/perfil-auth",
     * summary="Datos del usuario autenticado",
     * tags={"Auth"},
     * security={{"sanctum": {}}},
     * @OA\Response(response=200, description="Datos de perfil")
     * )
     */
    public function perfil(Request $request)
    {
       try {
       
        $user = $request->user();

     //para refresh en angular f5
        if (!$user) {
            return response()->json([
                'mensaje' => 'No se encontró el perfil del usuario.'
            ], 404);
        }

        
        $responseData = [
            'usuario'     => $user->name,
            'rol'         => strtolower($user->rol->rol),
            'change_pass' => (int) $user->change_pass
        ];

      
        if ($user->role_id === 1) {
            $responseData['user'] = $user->id;
        }

        return response()->json($responseData, 200);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Error al recuperar los datos del perfil.',
        ], 500);
    }
    }
}
