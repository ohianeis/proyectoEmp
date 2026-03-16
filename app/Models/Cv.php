<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Cv extends Model
{
    protected $fillable = ['nombre', 'url', 'demandante_id'];

    // Para que front reciba la URL lista para abrir
    protected $appends = ['full_url'];

    public function getFullUrlAttribute()
    {
        return asset('storage/' . $this->url);
    }
    
    public function demandante(){
        return $this->belongsTo(Demandante::class);
    }
}
