<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $fillable = [
        'nombre',
        'activa'
    ];
    //
 public function titulos() {
    return $this->hasMany(Titulo::class);
}
}
