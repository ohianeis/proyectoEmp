<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DemandanteOferta extends Pivot
{
    //
    protected $table='demandante_oferta';
    public $timestamps = true;

    //relacion tabla proceso
    public function proceso(){
        return $this->belongsTo(Proceso::class);
    }
}
