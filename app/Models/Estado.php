<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Estado extends Model
{
    //
    protected function createdAt(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $value = \Carbon\Carbon::parse($value); //pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    protected function updatedAt(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $value = \Carbon\Carbon::parse($value); //pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    public function ofertas(){
       // $oferta= Oferta::where('estado_id',$this->id)->first();
//realción 1:muchos
//desde estado accedo a oferta, un estado esta en muchas ofertas -->hasMany
        return $this->hasMany(Oferta::class);
    }
}
