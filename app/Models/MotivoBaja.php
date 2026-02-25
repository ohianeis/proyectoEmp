<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoBaja extends Model
{
    //
    protected $fillable = [
    'nombre',
    'visible_alumno',
    'visible_empresa',
    'solo_admin'
];

// Relación: Un motivo puede estar en muchas solicitudes
public function solicitudes()
{
    return $this->hasMany(SolicitudBaja::class);
}
}
