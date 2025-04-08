<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Demandante extends Model
{
    //
    protected $fillable = [
        'nombre',
        'telefono',
        'experienciaLaboral',
        'situacione_id',

    ];
    protected $guarded = [
        'centro_id',
        'user_id',
    ];
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
    protected function nombre(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    protected function experienciaLaboral(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    
    public function situacion()
    {
        return $this->belongsTo(Situacione::class, 'situacione_id');
    }
    public function centro()
    {
        return $this->belongsTo(Centro::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function cvs()
    {
        return $this->hasMany(Cv::class);
    }
    //muchos a muchos
    public function ofertas()
    {
        return $this->belongsToMany(Oferta::class)
            ->withPivot('fecha')
            ->withTimestamps();
        //->using(DemandanteOferta::class);
    }
    public function titulos()
    {
        return $this->belongsToMany(Titulo::class, 'demandante_titulo')->withPivot('centro', 'año', 'cursando');
    }
    //relacion 1:1 polimorfica
    public function direccion()
    {
        return $this->morphOne(Direccione::class, 'direccioneable');
    }
}
