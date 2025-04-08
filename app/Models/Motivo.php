<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motivo extends Model
{
    //
    //relacion 1 amuchos
    public function ofertas(){
        return $this->hasMany(Oferta::class);
    }
    
}
