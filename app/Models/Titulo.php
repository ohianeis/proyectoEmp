<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Titulo extends Model
{
    //
    protected $fillable = [
        'nombre',
        'activado',
        'nivele_id',
        'familia_id',
        'centro_id',
    ];
    use HasFactory;
    
    public function nivel(){
        return $this->belongsTo(Nivele::class,'nivele_id');
    }
    public function centro(){
        return $this->belongsTo(Centro::class);
    }
   
    //relacion muchos a muchos
    public function ofertas(){
        return $this->belongsToMany(Oferta::class)
                    ->withTimestamps();
    }
    public function demandantes(){
        return $this->belongsToMany(Demandante::class)
        ->withTimestamps();
    }
        //reacion 1 a mcuhos polimorfica
       
        public function familia() {
    return $this->belongsTo(Familia::class);
}
        
}
