<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'usuarios';
    public $timestamps = false;

    //fillable indica que campos pueden entrar en el request
    protected $fillable = [
        'correo',
        'usuario',
        'contraseña',
    ];
}
