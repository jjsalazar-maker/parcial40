<?php

namespace App\Validators;

use DateTime;
use InvalidArgumentException;

class ConductorValidator
{
    public const ESTADOS_VALIDOS = [
        'disponible',
        'en_ruta',
        'inactivo',
    ];

    public function validarCreacion(array $data): array
    {
        $nombres = $this->validarTextoObligatorio($data['nombres'] ?? null, 'nombres');
        $apellidos = $this->validarTextoObligatorio($data['apellidos'] ?? null, 'apellidos');
        $documento = $this->validarTextoObligatorio($data['documento'] ?? null, 'documento');
        $telefono = $this->validarTextoObligatorio($data['telefono'] ?? null, 'telefono');
        $correo = $this->validarCorreo($data['correo'] ?? null);
        $numeroLicencia = $this->validarTextoObligatorio($data['numero_licencia'] ?? null, 'numero_licencia');
        $categoriaLicencia = strtoupper($this->validarTextoObligatorio($data['categoria_licencia'] ?? null, 'categoria_licencia'));
        $fechaVencimiento = $this->validarFechaVencimiento($data['fecha_vencimiento_licencia'] ?? null);

        $estado = array_key_exists('estado', $data) && trim((string)$data['estado']) !== ''
            ? $this->validarEstado($data['estado'])
            : 'disponible';

        return [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'documento' => $documento,
            'telefono' => $telefono,
            'correo' => $correo,
            'numero_licencia' => $numeroLicencia,
            'categoria_licencia' => $categoriaLicencia,
            'fecha_vencimiento_licencia' => $fechaVencimiento,
            'estado' => $estado,
        ];
    }

    public function validarActualizacion(array $data): array
    {
        $limpios = [];

        if (array_key_exists('nombres', $data)) {
            $limpios['nombres'] = $this->validarTextoObligatorio($data['nombres'], 'nombres');
        }

        if (array_key_exists('apellidos', $data)) {
            $limpios['apellidos'] = $this->validarTextoObligatorio($data['apellidos'], 'apellidos');
        }

        if (array_key_exists('documento', $data)) {
            $limpios['documento'] = $this->validarTextoObligatorio($data['documento'], 'documento');
        }

        if (array_key_exists('telefono', $data)) {
            $limpios['telefono'] = $this->validarTextoObligatorio($data['telefono'], 'telefono');
        }

        if (array_key_exists('correo', $data)) {
            $limpios['correo'] = $this->validarCorreo($data['correo']);
        }

        if (array_key_exists('numero_licencia', $data)) {
            $limpios['numero_licencia'] = $this->validarTextoObligatorio($data['numero_licencia'], 'numero_licencia');
        }

        if (array_key_exists('categoria_licencia', $data)) {
            $limpios['categoria_licencia'] = strtoupper(
                $this->validarTextoObligatorio($data['categoria_licencia'], 'categoria_licencia')
            );
        }

        if (array_key_exists('fecha_vencimiento_licencia', $data)) {
            $limpios['fecha_vencimiento_licencia'] = $this->validarFechaVencimiento($data['fecha_vencimiento_licencia']);
        }

        if (array_key_exists('estado', $data)) {
            $limpios['estado'] = $this->validarEstado($data['estado']);
        }

        if ($limpios === []) {
            throw new InvalidArgumentException('No se enviaron campos válidos para actualizar.');
        }

        return $limpios;
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

    public function validarFechaVencimiento(mixed $fecha): string
    {
        if (!is_string($fecha) || trim($fecha) === '') {
            throw new InvalidArgumentException('El campo fecha_vencimiento_licencia es obligatorio (YYYY-MM-DD).');
        }

        $valor = trim($fecha);
        $dt = DateTime::createFromFormat('Y-m-d', $valor);
        if (!$dt || $dt->format('Y-m-d') !== $valor) {
            throw new InvalidArgumentException('La fecha_vencimiento_licencia debe tener formato YYYY-MM-DD.');
        }

        $hoy = date('Y-m-d');
        if ($valor < $hoy) {
            throw new InvalidArgumentException('La licencia está vencida; la fecha_vencimiento_licencia debe ser igual o posterior a hoy.');
        }

        return $valor;
    }

    private function validarTextoObligatorio(mixed $valor, string $campo): string
    {
        $texto = is_scalar($valor) ? trim((string)$valor) : '';
        if ($texto === '') {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio.");
        }

        return $texto;
    }

    private function validarCorreo(mixed $correo): string
    {
        $valor = is_string($correo) ? trim($correo) : '';
        if ($valor === '' || !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Debe proporcionar un correo electrónico válido.');
        }

        return $valor;
    }
}
