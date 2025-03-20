<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    //

    public function ofertas(){
       // $oferta= Oferta::where('estado_id',$this->id)->first();
//realción 1:muchos
//desde estado accedo a oferta, un estado esta en muchas ofertas -->hasMany
        return $this->hasMany(Oferta::class);
    }
}
