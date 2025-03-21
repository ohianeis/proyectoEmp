<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demandante extends Model
{
    //
    public function situacion(){
        return $this->belongsTo(Situacione::class,'situacione_id');
    }
    public function centro(){
        return $this->belongsTo(Centro::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function cvs(){
        return $this->hasMany(Cv::class);
    }
    //muchos a muchos
    public function ofertas(){
        return $this->belongsToMany(Oferta::class)
        ->withPivot('fecha','proceso_id')
        ->withTimestamps();
        //->using(DemandanteOferta::class);
    }
    public function titulos(){
        return $this->belongsToMany(Titulo::class)
                    ->withTimestamps();
    }
            //relacion 1:1 polimorfica
            public function direccion(){
                return $this->morphOne(Direccione::class,'direccioneable');
            }

}
