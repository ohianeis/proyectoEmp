<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Titulo extends Model
{
    //
    public function nivel(){
        return $this->belongsTo(Nivele::class);
    }
    public function centro(){
        return $this->belongsTo(Centro::class);
    }
    public function empresas(){
        return $this->belongsToMany(Empresa::class)
        ->withTimestamps();
    }
    //relacion muchos a muchos
    public function ofertas(){
        return $this->belongsToMany(Oferta::class)
                    ->withTimestamps();
    }
    public function demandantes(){
        return $this->belongsToMany(Demandante::class)
        ->withTimestamps();
    }
        //reacion 1 a mcuhos polimorfica
        public function notificaciones(){
            return $this->morphMany(Notificacione::class,'relacioneable');
        }
        
}
