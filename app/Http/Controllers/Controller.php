<?php

namespace App\Http\Controllers;
use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 * title="API Bolsa de Empleo",
 * version="1.0.0",
 * description="Sistema de gestión de ofertas de empleo y seguimiento de candidatos.",
 * @OA\Contact(
 * email="admin@centro.com"
 * )
 * )
 *
 * @OA\Server(
 * url="http://localhost:8000",
 * description="Servidor de Desarrollo Local"
 * )
 *
 * @OA\SecurityScheme(
 * type="http",
 * securityScheme="sanctum",
 * scheme="bearer",
 * bearerFormat="JWT",
 * description="Introduce el token obtenido en el login para acceder a las rutas protegidas"
 * )
 */
abstract class Controller
{
    //
}
