<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'usuarios';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'correo',
        'usuario',
        'contrasena',
        'rol',
        'token',
        'sesion_activa',
        'estado',
    ];

    protected $hidden = [
        'contrasena',
        'token',
    ];

    protected $casts = [
        'id' => 'integer',
        'sesion_activa' => 'boolean',
    ];
}
