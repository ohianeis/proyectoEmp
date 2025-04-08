<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direccione extends Model
{
    //
    use HasFactory;
    protected $fillable=[
        'linea1','linea2','ciudad','provincia','codigoPostal','pais','visible','direccioneable_id','direccioneable_type'
    ];
    protected $hidden = [
        'direccioneable_id',
        'direccioneable_type'
    ];
    protected function linea1(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    protected function linea2(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    protected function ciudad(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    protected function provincia(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    protected function pais(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                
                return strtolower($value);
            }
        );
    }
    protected function createdAt():Attribute{
        return new Attribute(
            get: function($value){
                $value=\Carbon\Carbon::parse($value);//pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    protected function updatedAt():Attribute{
        return new Attribute(
            get: function($value){
                $value=\Carbon\Carbon::parse($value);//pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    public function direccioneable(){
        return $this->morphTo();
    }
}
