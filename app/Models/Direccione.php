<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direccione extends Model
{
    //
    use HasFactory;
    protected $fillable=[
        'linea1','linea2','ciudad','provincia','codigoPostal','pais','visible','direccioneable_id','direccioneable_type'
    ];
    public function direccioneable(){
        return $this->morphTo();
    }
}
