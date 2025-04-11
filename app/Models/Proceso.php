<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proceso extends Model
{
    //
    
    //relacion con demanteOfertas
    public function demandantesOfertas(){
        return $this->hasMany(DemandanteOferta::class);
    }
}
