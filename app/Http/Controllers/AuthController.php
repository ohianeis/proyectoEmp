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
 * @OA\Info(
 *              title="API Proyecto bolsa empleo",
 *              version="1.0",
 *              description="Listado de URI'S para el proyecto bolsa empleo"
 * )
 * 
 * @OA\Server(url="http://127.0.0.1/laravel/bolsaEmp/public")//url para la docum de wagger añadir /api/documentation en url
 */
class AuthController extends Controller
{
    //
    /**
 * @OA\Get(
 *     path="/api/registro/roles",
 *     summary="Obtener lista de roles disponibles",
 *     description="Devuelve una lista de roles excepto el administrador (ID 1).",
 *     tags={"Auth"},
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     @OA\Response(
 *         response=200,
 *         description="Lista de roles obtenida correctamente.",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=2, description="ID del rol."),
 *                 @OA\Property(property="rol", type="string", example="Empresa", description="Nombre del rol.")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="mensaje", type="string", example="Se produjo un error al obtener los roles.")
 *         )
 *     )
 * )
 */

    public function roles(){
        try{
            $tiposRoles=Role::select('id','rol')->where('id','!=',1)->orderby('id')->get();
            return response()->json($tiposRoles,200);
        }catch(Exception $e){
            return response()->json([
                'mensaje'=>$e->getMessage()
            ],500);
        }
    }
   /**
 * Registro a la aplicación
 * @OA\Post(
 *      path="/api/registro",
 *      tags={"Auth"},
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              required={"name","email","password","role"},
 *              @OA\Property(property="name", type="string", example="ohiane"),
 *              @OA\Property(property="email", type="string", format="email", example="ohiane@ejemplo.com"),
 *              @OA\Property(property="password", type="string", format="password", description="Debe tener al menos 6 caracteres", minLength=6, example="123456789"),
 *              @OA\Property(property="role", type="integer", description="Debe corresponder a un ID válido en la tabla roles", example=1)
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="OK",
 *          @OA\JsonContent(
 *              @OA\Property(property="mensaje", type="boolean", example=true),
 *              @OA\Property(property="usuario", type="string", example="ohiane"),
 *              @OA\Property(property="token", type="string", example="6|LqkDJBeDDN94QsvagE40frw1I11sDOBs5XclO7es38384cb3"),
 *              @OA\Property(property="token_type", type="string", example="Bearer")
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Error de validación.",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="El correo electrónico ya está registrado"),
 *              @OA\Property(property="errors", type="object",
 *                  @OA\Property(property="email", type="array",
 *                      @OA\Items(type="string", example="El correo electrónico ya está registrado")
 *                  ),
 *                  @OA\Property(property="role", type="array",
 *                      @OA\Items(type="string", example="El rol seleccionado no es válido")
 *                  )
 *              )
 *          )
 *      )
 * )
 */
    public function registro(Request $request){
       $request->validate([
            'name'=>'required|string|max:100',
            'email'=>'required|string|email|max:255|unique:users',
            'password'=>'required|string|min:6',
            'role'=>'required|integer|in:2,3|exists:roles,id',
       ], [
                'role.exists'=>'El rol seleccionado no es válido',
                'email.email'=>'Por favor, introduce un correo electrónico válido',
                'email.unique'=>'El correo electrónico ya está registrado',
            ]
       );
  

       $user=User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'validado'=>0,
            'role_id'=>$request->role
       ]);
       $token=$user->createToken('auth_token')->plainTextToken;
       return response()->json([
            'data'=>$user,'access_token'=>$token,'token_type'=>'Bearer',
       ]);
    }
    /**
 * Login a la aplicación
 * @OA\Post(
 *      path="/api/login",
 *      summary="Inicio de sesión de usuario",
 *      description="Permite a un usuario autenticarse en la aplicación.",
 *      tags={"Auth"},
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              required={"email","password"},
 *              @OA\Property(property="email", type="string", format="email", example="ohiane@ejemplo.com"),
 *              @OA\Property(property="password", type="string", format="password", description="Debe tener al menos 6 caracteres", example="123456789")
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Inicio de sesión exitoso.",
 *          @OA\JsonContent(
 *              @OA\Property(property="mensaje", type="boolean", example=true),
 *              @OA\Property(property="usuario", type="string", example="ohiane"),
 *              @OA\Property(property="token", type="string", example="6|LqkDJBeDDN94QsvagE40frw1I11sDOBs5XclO7es38384cb3"),
 *              @OA\Property(property="token_type", type="string", example="Bearer")
 *          )
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Error de autenticación.",
 *          @OA\JsonContent(
 *              @OA\Property(property="mensaje", type="string", example="Usuario no autorizado")
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Error de validación.",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="Error de validación"),
 *              @OA\Property(property="errors", type="object",
 *                  @OA\Property(property="email", type="array",
 *                      @OA\Items(type="string", example="El campo email es obligatorio.")
 *                  ),
 *                  @OA\Property(property="password", type="array",
 *                      @OA\Items(type="string", example="El campo password es obligatorio.")
 *                  )
 *              )
 *          )
 *      )
 * )
 */
    public function login(Request $request){
         // Validación de los datos entrantes
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);
        if(!Auth::attempt($request->only('email','password'))){
            return response()->json(['mensaje'=>'Usuario no autorizado'],401);

        }
        $user =User::where('email',$request->email)->firstOrFail();
        $abilities=[];
        switch($user->role_id){
            case 1://administrador
                $abilities=['administrador'];
                break;
            case 2://empresa
                $abilities=['empresa'];
                break;
            case 3://demandante
                $abilities=['demandante'];
                break;
        }
        $token=$user->createToken('auth_token',$abilities)->plainTextToken;

        return response()->json([
            'mensaje'=>true,
            'usuario'=>$user->name,
            'token'=>$token,
            'token_type'=>'Bearer'
        ]);
    }
}
