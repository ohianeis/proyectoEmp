<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Centro extends Model
{
    //
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function empresas(){
        return $this->hasMany(Empresa::class);
    }
    public function demandantes(){
        return $this->hasMany(Demandante::class);
    }
    public function titulos(){
        return $this->hasMany(Titulo::class);
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
