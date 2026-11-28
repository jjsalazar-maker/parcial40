<?php

namespace App\Validators;

use InvalidArgumentException;

class VehiculoValidator
{
    public const ESTADOS_VALIDOS = [
        'disponible',
        'en_ruta',
        'mantenimiento',
        'inactivo',
    ];

    public function validarCreacion(array $data): array
    {
        $placa = $this->normalizarPlaca($data['placa'] ?? null);
        $tipo = $this->validarTextoObligatorio(
            $data['tipo_vehiculo'] ?? $data['tipo'] ?? null,
            'tipo_vehiculo'
        );
        $capacidad = $this->validarCapacidad(
            $data['capacidad_carga'] ?? $data['capacidad'] ?? null
        );
        $modelo = $this->validarTextoObligatorio($data['modelo'] ?? null, 'modelo');
        $marca = $this->validarTextoObligatorio($data['marca'] ?? null, 'marca');

        $estado = array_key_exists('estado', $data) && trim((string)$data['estado']) !== ''
            ? $this->validarEstado($data['estado'])
            : 'disponible';

        return [
            'placa' => $placa,
            'tipo_vehiculo' => $tipo,
            'capacidad_carga' => $capacidad,
            'modelo' => $modelo,
            'marca' => $marca,
            'estado' => $estado,
        ];
    }

    public function validarActualizacion(array $data): array
    {
        $limpios = [];

        if (array_key_exists('placa', $data)) {
            $limpios['placa'] = $this->normalizarPlaca($data['placa']);
        }

        if (array_key_exists('tipo_vehiculo', $data)) {
            $limpios['tipo_vehiculo'] = $this->validarTextoObligatorio($data['tipo_vehiculo'], 'tipo_vehiculo');
        } elseif (array_key_exists('tipo', $data)) {
            $limpios['tipo_vehiculo'] = $this->validarTextoObligatorio($data['tipo'], 'tipo_vehiculo');
        }

        if (array_key_exists('capacidad_carga', $data)) {
            $limpios['capacidad_carga'] = $this->validarCapacidad($data['capacidad_carga']);
        } elseif (array_key_exists('capacidad', $data)) {
            $limpios['capacidad_carga'] = $this->validarCapacidad($data['capacidad']);
        }

        if (array_key_exists('modelo', $data)) {
            $limpios['modelo'] = $this->validarTextoObligatorio($data['modelo'], 'modelo');
        }

        if (array_key_exists('marca', $data)) {
            $limpios['marca'] = $this->validarTextoObligatorio($data['marca'], 'marca');
        }

        if (array_key_exists('estado', $data)) {
            $limpios['estado'] = $this->validarEstado($data['estado']);
        }

        if ($limpios === []) {
            throw new InvalidArgumentException('No se enviaron campos válidos para actualizar.');
        }

        return $limpios;
    }

    public function normalizarPlaca(mixed $placa): string
    {
        $texto = $this->validarTextoObligatorio($placa, 'placa');
        return strtoupper($texto);
    }

    public function validarCapacidad(mixed $capacidad): float
    {
        if ($capacidad === null || $capacidad === '' || !is_numeric($capacidad)) {
            throw new InvalidArgumentException('El campo capacidad_carga es obligatorio y debe ser numérico.');
        }

        $valor = (float)$capacidad;
        if ($valor <= 0) {
            throw new InvalidArgumentException('La capacidad de carga debe ser mayor a cero.');
        }

        return $valor;
    }

    public function validarEstado(mixed $estado): string
    {
        if (!is_string($estado) || trim($estado) === '') {
            throw new InvalidArgumentException(
                'El estado es obligatorio. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . '.'
            );
        }

        $normalizado = strtolower(trim($estado));
        $normalizado = str_replace([' ', '-'], '_', $normalizado);

        if (!in_array($normalizado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . '.'
            );
        }

        return $normalizado;
    }

    private function validarTextoObligatorio(mixed $valor, string $campo): string
    {
        $texto = is_scalar($valor) ? trim((string)$valor) : '';
        if ($texto === '') {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio.");
        }

        return $texto;
    }
}
