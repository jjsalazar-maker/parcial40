<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';
    public $timestamps = false;

    protected $fillable = [
        'placa',
        'tipo_vehiculo',
        'capacidad_carga',
        'modelo',
        'marca',
        'estado',
    ];

    protected $casts = [
        'id' => 'integer',
        'capacidad_carga' => 'float',
    ];
}
