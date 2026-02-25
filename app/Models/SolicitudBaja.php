<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudBaja extends Model
{
    //
    protected $fillable = [
    'user_id',
    'motivo_baja_id',
    'comentario',
    'tramitada'
];

// Relación: La solicitud pertenece a un usuario
public function user()
{
    return $this->belongsTo(User::class);
}

// Relación: La solicitud tiene un motivo específico
public function motivoBaja()
{
    return $this->belongsTo(MotivoBaja::class);
}
}
