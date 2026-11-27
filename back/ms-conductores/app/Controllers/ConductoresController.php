<?php

namespace App\Controllers;

use App\Models\Conductor;
use App\Validators\ConductorValidator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use RuntimeException;

class ConductoresController implements ConductoresControllerInterface
{
    private ConductorValidator $validator;

    public function __construct(?ConductorValidator $validator = null)
    {
        $this->validator = $validator ?? new ConductorValidator();
    }

    public function get_conductores(array $filtros = []): Collection
    {
        $query = Conductor::query();

        if (!empty($filtros['documento'])) {
            $query->where('documento', trim((string)$filtros['documento']));
        }

        $licencia = $filtros['numero_licencia'] ?? $filtros['licencia'] ?? null;
        if (!empty($licencia)) {
            $query->where('numero_licencia', trim((string)$licencia));
        }

        if (!empty($filtros['estado'])) {
            $estadoValido = $this->validator->validarEstado($filtros['estado']);
            $query->where('estado', $estadoValido);
        }

        return $query->orderBy('id', 'asc')->get();
    }

    public function find_conductor(int $id): Conductor
    {
        return Conductor::findOrFail($id);
    }

    public function find_by_documento(string $documento): Conductor
    {
        $doc = trim($documento);
        if ($doc === '') {
            throw new InvalidArgumentException('Debe proporcionar un documento válido.');
        }

        return Conductor::where('documento', $doc)->firstOrFail();
    }

    public function find_by_licencia(string $numeroLicencia): Conductor
    {
        $lic = trim($numeroLicencia);
        if ($lic === '') {
            throw new InvalidArgumentException('Debe proporcionar un número de licencia válido.');
        }

        return Conductor::where('numero_licencia', $lic)->firstOrFail();
    }

    public function get_by_estado(string $estado): Collection
    {
        $estadoValido = $this->validator->validarEstado($estado);

        return Conductor::where('estado', $estadoValido)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function create_conductor(array $data): Conductor
    {
        $limpios = $this->validator->validarCreacion($data);
        $this->asegurarUnicidad($limpios);

        $conductor = new Conductor();
        $conductor->nombres = $limpios['nombres'];
        $conductor->apellidos = $limpios['apellidos'];
        $conductor->documento = $limpios['documento'];
        $conductor->telefono = $limpios['telefono'];
        $conductor->correo = $limpios['correo'];
        $conductor->numero_licencia = $limpios['numero_licencia'];
        $conductor->categoria_licencia = $limpios['categoria_licencia'];
        $conductor->fecha_vencimiento_licencia = $limpios['fecha_vencimiento_licencia'];
        $conductor->estado = $limpios['estado'];
        $conductor->save();

        return $conductor;
    }

    public function update_conductor(int $id, array $data): Conductor
    {
        $conductor = Conductor::findOrFail($id);
        $limpios = $this->validator->validarActualizacion($data);
        $this->asegurarUnicidad($limpios, $id);

        foreach ($limpios as $campo => $valor) {
            $conductor->{$campo} = $valor;
        }
        $conductor->save();

        return $conductor;
    }

    public function change_estado(int $id, string $estado): Conductor
    {
        $conductor = Conductor::findOrFail($id);
        $conductor->estado = $this->validator->validarEstado($estado);
        $conductor->save();

        return $conductor;
    }

    public function delete_conductor(int $id): Conductor
    {
        $conductor = Conductor::findOrFail($id);
        $conductor->delete();

        return $conductor;
    }

    private function asegurarUnicidad(array $datos, ?int $excludeId = null): void
    {
        if (isset($datos['documento'])) {
            $query = Conductor::where('documento', $datos['documento']);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                throw new RuntimeException('Ya existe un conductor registrado con ese documento.', 409);
            }
        }

        if (isset($datos['numero_licencia'])) {
            $query = Conductor::where('numero_licencia', $datos['numero_licencia']);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                throw new RuntimeException('Ya existe un conductor registrado con ese número de licencia.', 409);
            }
        }

        if (isset($datos['correo'])) {
            $query = Conductor::where('correo', $datos['correo']);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                throw new RuntimeException('Ya existe un conductor registrado con ese correo electrónico.', 409);
            }
        }
    }
}
