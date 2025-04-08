<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Oferta extends Model
{
    use HasFactory;
    //
    protected $fillable=[
        'nombre',
        'observacion',
        'tipoContrato',
        'horario',

        'nPuestos',

    ];
    protected $guarded=[
        'fechaCierre',
        'motivo_id',
        'estado_id',
        'empresa_id'
    ];
    protected $hidden = ['pivot'];

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
 protected function fechaCierre(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null
    );
}
    //relacion 1:muchos desde ofeerta recupera estado usamos metodo find porque buscamos por un id, le paso el id de estado_id y me da el id de la tabla estados

    //relacion uno a muchos inversa, una oferta tiene un estado -->belongsTo
    public function estado(){
        return $this->belongsTo(Estado::class);
    }
    public function motivo(){
        return $this->belongsTo(Motivo::class);
    }
    //relacion muchos a muchos
    public function demandantes(){
        return $this->belongsToMany(Demandante::class)
        ->withPivot('fecha','proceso_id')
        ->withTimestamps();
        //->using(DemandanteOferta::class);
    }
    //muchos a muchos
    public function titulos(){
        return $this->belongsToMany(Titulo::class)
                    ->withTimestamps();
    }
    public function empresa(){
        return $this->belongsTo(Empresa::class);
    }
 //reacion 1 a mcuhos polimorfica
     public function notificaciones(){
        return $this->morphMany(Notificacione::class,'relacioneable');
    }
}
