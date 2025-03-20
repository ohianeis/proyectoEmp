<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfertaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('ofertas',[OfertaController::class,'index']);
Route::get('/',function(){
    return 'estoy en api';
});
Route::post('registro',[AuthController::class,'registro']);
Route::post('login',[AuthController::class,'login']);