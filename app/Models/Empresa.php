<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = [
        'cif',
        'nombre',
        'localidad',

    ];
    //no se pueda modificar extrnamente user_id ni centro_id
    protected $guarded = [
        'user_id',
        'centro_id',
    ];
    //setters para guardar los datos en minúsculas en la tabla
    protected function cif(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
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
    protected function localidad(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
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
    public function centro()
    {
        return $this->belongsTo(Centro::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //relacion muchos a muchos
    public function titulos()
    {
        return $this->belongsToMany(Titulo::class)
            ->withTimestamps();
    }
    public function ofertas()
    {
        return $this->hasMany(Oferta::class);
    }
    //reacion 1 a mcuhos polimorfica
    public function notificaciones()
    {
        return $this->morphMany(Notificacione::class, 'notificacioneable');
    }
    //relacion 1:1 polimorfica
    public function direccion()
    {
        return $this->morphOne(Direccione::class, 'direccioneable');
    }
}
