<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandanteTitulo extends Model
{
    //
    protected $table = 'demandante_titulo'; // Nombre de la tabla
    public $timestamps = false; // Si la tabla no tiene created_at y updated_at
    
    protected $fillable = ['centro', 'año', 'cursando', 'titulo_id', 'demandante_id']; // Columnas que se pueden llenar
}
