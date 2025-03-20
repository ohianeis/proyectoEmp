<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandanteNotificacion extends Model
{
    //creo tabla por valo boolean para la vista de notificaciones
    protected $table='demandante_notificacione';
    public $timestamps=true;
}
