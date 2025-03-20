<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    //
    public function demandante(){
        return $this->belongsTo(Demandante::class);
    }
}
