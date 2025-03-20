<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivele extends Model
{
    //
    public function titulos(){
        return $this->hasMany(Titulo::class);
    }
}
