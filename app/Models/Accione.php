<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accione extends Model
{
    //
    public function notificaciones(){
      return  $this->hasMany(Notificacione::class,'accione_id');
    }
    //relacion 1:1 con mensaje 1 accion tiene un mensaje
    public function mensaje(){
       return $this->belongsTo(Mensaje::class);
    }
}
