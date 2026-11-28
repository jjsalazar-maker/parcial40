<?php

namespace App\Controllers;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;

interface VehiculosControllerInterface
{
    public function get_vehiculos(array $filtros = []): Collection;

    public function find_vehiculo(int $id): Vehiculo;

    public function find_by_placa(string $placa): Vehiculo;

    public function get_by_estado(string $estado): Collection;

    public function get_by_tipo(string $tipo): Collection;

    public function create_vehiculo(array $data): Vehiculo;

    public function update_vehiculo(int $id, array $data): Vehiculo;

    public function change_estado(int $id, string $estado): Vehiculo;

    public function delete_vehiculo(int $id): Vehiculo;
}
