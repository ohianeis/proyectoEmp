<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    use HasFactory;
    //
    //relacion 1:muchos desde ofeerta recupera estado usamos metodo find porque buscamos por un id, le paso el id de estado_id y me da el id de la tabla estados

    //relacion uno a muchos inversa, una oferta tiene un estado -->belongsTo
    public function estado(){
        return $this->belongsTo(Estado::class);
    }
    //relacion muchos a muchos
    public function demandantes(){
        return $this->belongsToMany(Demandante::class)
        ->withPivot('fecha','proceso_id')
        ->withTimestamps();
        //->using(DemandanteOferta::class);
    }
    //muchos a muchos
    public function titulos(){
        return $this->belongsToMany(Titulo::class)
                    ->withTimestamps();
    }
    public function empresa(){
        return $this->hasOne(Empresa::class);
    }
 //reacion 1 a mcuhos polimorfica
     public function notificaciones(){
        return $this->morphMany(Notificacione::class,'relacioneable');
    }
}
