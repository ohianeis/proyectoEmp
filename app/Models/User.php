<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserEstado;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'validado',
        'role_id',
        'status',           
    'motivo_baja_id',    
    'comentario_baja',  
    'fecha_baja'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserEstado::class,//casting enums
            'fecha_baja' => 'datetime',
        ];
    }
    protected function createdAt():Attribute{
        return new Attribute(
            get: function($value){
                $value=\Carbon\Carbon::parse($value);//pasar el string formato fecha
                return $value->format('d/m/Y');
            }
        );
    }
    public function rol(){
        return $this->belongsTo(Role::class,'role_id');
    }
    public function centro(){
        return $this->hasOne(Centro::class);

    }
    public function empresa(){
        return $this->hasOne(Empresa::class);
    }
    public function demandante(){
        return $this->hasOne(Demandante::class);
    }
    public function motivoBaja()
{
    return $this->belongsTo(MotivoBaja::class, 'motivo_baja_id');
}
}
