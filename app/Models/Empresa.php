<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    //
    public function centro(){
        return $this->belongsTo(Centro::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    //relacion muchos a muchos
    public function titulos(){
        return $this->belongsToMany(Titulo::class)
        ->withTimestamps();
    }
    public function ofertas(){
        return $this->hasMany(Oferta::class);
    }
        //reacion 1 a mcuhos polimorfica
        public function notificaciones(){
            return $this->morphMany(Notificacione::class,'notificacioneable');
        }
        //relacion 1:1 polimorfica
        public function direccion(){
            return $this->morphOne(Direccione::class,'direccioneable');
        }
}
