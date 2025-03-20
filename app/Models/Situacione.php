<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Situacione extends Model
{
    //
    public function demandantes(){
        return $this->hasMany(Demandante::class,'situacione_id');
    }
}
