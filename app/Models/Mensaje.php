<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    //
    //relacion 1:1 con accion/ un mensaje tiene una accion
    public function accion(){
      return  $this->hasOne(Accione::class);
    }
}
