<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacione extends Model
{
    //
    protected $fillable = [
      'accione_id',
      'notificacioneable_id',
      'notificacioneable_type',
      'relacioneable_id',
      'relacioneable_type',
  ];
    //relacion 1:muchos Una notificacion solo tiene una accion y mensaje
    public function accion(){
      return  $this->belongsTo(Accione::class,'accione_id');
    }
    //tabla polifmorfica
    public function notificacioneable(){
      return $this->morphTo();
    }
        //tabla polifmorfica
        public function relacioneable(){
          return $this->morphTo();
        }
   
    
}
