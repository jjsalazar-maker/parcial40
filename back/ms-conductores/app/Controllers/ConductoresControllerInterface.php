<?php

namespace App\Controllers;

use App\Models\Conductor;
use Illuminate\Database\Eloquent\Collection;

interface ConductoresControllerInterface
{
    public function get_conductores(array $filtros = []): Collection;

    public function find_conductor(int $id): Conductor;

    public function find_by_documento(string $documento): Conductor;

    public function find_by_licencia(string $numeroLicencia): Conductor;

    public function get_by_estado(string $estado): Collection;

    public function create_conductor(array $data): Conductor;

    public function update_conductor(int $id, array $data): Conductor;

    public function change_estado(int $id, string $estado): Conductor;

    public function delete_conductor(int $id): Conductor;
}
