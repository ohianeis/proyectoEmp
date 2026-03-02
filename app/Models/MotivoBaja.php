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
    'solo_admin',
    'activo'
];

public function usuarios()
    {
        return $this->hasMany(User::class, 'motivo_baja_id');
    }
}
