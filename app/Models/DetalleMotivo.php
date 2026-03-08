<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMotivo extends Model
{
    //
    protected $fillable = ['nombre', 'motivo_id', 'activo'];
    public function motivo() {
    return $this->belongsTo(Motivo::class);
}

//  Para saber qué ofertas se cerraron con este detalle
public function ofertas() {
    return $this->hasMany(Oferta::class);
}
}
