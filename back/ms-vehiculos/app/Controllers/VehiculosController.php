<?php

namespace App\Controllers;

use App\Models\Vehiculo;
use App\Validators\VehiculoValidator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use RuntimeException;

class VehiculosController implements VehiculosControllerInterface
{
    private VehiculoValidator $validator;

    public function __construct(?VehiculoValidator $validator = null)
    {
        $this->validator = $validator ?? new VehiculoValidator();
    }

    public function get_vehiculos(array $filtros = []): Collection
    {
        $query = Vehiculo::query();

        if (!empty($filtros['placa'])) {
            $query->where('placa', $this->validator->normalizarPlaca($filtros['placa']));
        }

        if (!empty($filtros['estado'])) {
            $estadoValido = $this->validator->validarEstado($filtros['estado']);
            $query->where('estado', $estadoValido);
        }

        $tipo = $filtros['tipo_vehiculo'] ?? $filtros['tipo'] ?? null;
        if (!empty($tipo)) {
            $query->where('tipo_vehiculo', trim((string)$tipo));
        }

        return $query->orderBy('id', 'asc')->get();
    }

    public function find_vehiculo(int $id): Vehiculo
    {
        return Vehiculo::findOrFail($id);
    }

    public function find_by_placa(string $placa): Vehiculo
    {
        $placaNormalizada = $this->validator->normalizarPlaca($placa);
        return Vehiculo::where('placa', $placaNormalizada)->firstOrFail();
    }

    public function get_by_estado(string $estado): Collection
    {
        $estadoValido = $this->validator->validarEstado($estado);

        return Vehiculo::where('estado', $estadoValido)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function get_by_tipo(string $tipo): Collection
    {
        $tipoLimpio = trim($tipo);
        if ($tipoLimpio === '') {
            throw new InvalidArgumentException('Debe proporcionar un tipo de vehículo válido.');
        }

        return Vehiculo::where('tipo_vehiculo', $tipoLimpio)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function create_vehiculo(array $data): Vehiculo
    {
        $limpios = $this->validator->validarCreacion($data);
        $this->asegurarPlacaUnica($limpios['placa']);

        $vehiculo = new Vehiculo();
        $vehiculo->placa = $limpios['placa'];
        $vehiculo->tipo_vehiculo = $limpios['tipo_vehiculo'];
        $vehiculo->capacidad_carga = $limpios['capacidad_carga'];
        $vehiculo->modelo = $limpios['modelo'];
        $vehiculo->marca = $limpios['marca'];
        $vehiculo->estado = $limpios['estado'];
        $vehiculo->save();

        return $vehiculo;
    }

    public function update_vehiculo(int $id, array $data): Vehiculo
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $limpios = $this->validator->validarActualizacion($data);

        if (isset($limpios['placa'])) {
            $this->asegurarPlacaUnica($limpios['placa'], $id);
        }

        foreach ($limpios as $campo => $valor) {
            $vehiculo->{$campo} = $valor;
        }
        $vehiculo->save();

        return $vehiculo;
    }

    public function change_estado(int $id, string $estado): Vehiculo
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->estado = $this->validator->validarEstado($estado);
        $vehiculo->save();

        return $vehiculo;
    }

    public function delete_vehiculo(int $id): Vehiculo
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->delete();

        return $vehiculo;
    }

    private function asegurarPlacaUnica(string $placa, ?int $excludeId = null): void
    {
        $query = Vehiculo::where('placa', $placa);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new RuntimeException('Ya existe un vehículo registrado con esa placa.', 409);
        }
    }
}
