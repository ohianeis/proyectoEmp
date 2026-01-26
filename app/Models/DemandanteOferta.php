<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\Attribute;


class DemandanteOferta extends Pivot
{
    //
    protected $fillable=[
        'fecha',
        'proceso_id',
        'demandante_id',
        'oferta_id'

    ];
    protected $table='demandante_oferta';
    public $timestamps = true;
    protected function createdAt(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $value = \Carbon\Carbon::parse($value); //pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    protected function updatedAt(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $value = \Carbon\Carbon::parse($value); //pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    protected function fecha(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $value = \Carbon\Carbon::parse($value); //pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    
    //relacion tabla proceso
    public function proceso() {
        return $this->belongsTo(Proceso::class, 'proceso_id');
    }
}
