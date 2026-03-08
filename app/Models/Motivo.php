<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motivo extends Model
{
    //
    //relacion 1 amuchos
    public function ofertas(){
        return $this->hasMany(Oferta::class);
    }
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleMotivo::class, 'motivo_id');
    }
}
