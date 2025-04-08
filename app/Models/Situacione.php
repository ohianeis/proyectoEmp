<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Situacione extends Model
{
    //
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
    public function demandantes(){
        return $this->hasMany(Demandante::class,'situacione_id');
    }
}
