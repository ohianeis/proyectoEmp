<?php

// routes/web.php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'service' => 'API Proyecto Empleo',
        'description' => 'Backend Headless para gestión de empleo',
        'version' => '1.0.0',
        'environment' => config('app.env')
    ], 200);
});
