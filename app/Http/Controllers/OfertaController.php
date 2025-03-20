<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Http\Request;

class OfertaController extends Controller
{
    //
    public function index(){
        $ofertas=Oferta::where('estado_id',1)->orderBy('created_at','desc')->get();
        return $ofertas;
    }
}
