<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'cif',
        'localidad',
        'descripcion',
        'web',
        'telefono_contacto'

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
    return Attribute::make(
        set: fn ($value) => $value ? strtolower($value) : null,
        
        get: fn ($value) => $value, 
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
   
  
    //relacion 1:1 polimorfica
    public function direccion()
    {
        return $this->morphOne(Direccione::class, 'direccioneable');
    }
}
