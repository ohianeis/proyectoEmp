<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoCandidato extends Model
{
    //
    public function demandantes()
{
    return $this->hasMany(Oferta::class, 'demandante_oferta');
}
}
