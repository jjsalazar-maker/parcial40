<?php

namespace App\Validators;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthValidator
{
    public const ROLES_VALIDOS = [
        'administrador',
        'logistica',
        'operador',
    ];

    public const ESTADOS_VALIDOS = [
        'activo',
        'inactivo',
    ];

    public function validarLogin(array $data): array
    {
        $identificador = $this->extraerPrimerCampo($data, [
            'identificador',
            'usuario',
            'correo',
            'username',
            'email',
        ]);6

        if ($identificador === '') {
            throw new InvalidArgumentException('Debe ingresar un usuario o correo.');
        }

        $contrasena = $this->extraerPrimerCampo($data, [
            'contrasena',
            'contraseña',
            'password',
        ], false);

        if (trim($contrasena) === '') {
            throw new InvalidArgumentException('Debe ingresar la contraseña.');
        }

        return [
            'identificador' => $identificador,
            'contrasena' => $contrasena,
        ];
    }

    public function validarCreacionUsuario(array $data): array
    {
        $nombre = isset($data['nombre']) ? trim((string)$data['nombre']) : '';
        if ($nombre === '') {
            throw new InvalidArgumentException('El campo nombre es obligatorio.');
        }

        $correo = isset($data['correo']) ? trim((string)$data['correo']) : '';
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Debe proporcionar un correo electrónico válido.');
        }

        $usuario = isset($data['usuario']) ? trim((string)$data['usuario']) : '';
        if ($usuario === '') {
            throw new InvalidArgumentException('El campo usuario es obligatorio.');
        }

        $contrasena = $this->extraerPrimerCampo($data, [
            'contrasena',
            'contraseña',
            'password',
        ], false);

        if (trim($contrasena) === '') {
            throw new InvalidArgumentException('El campo contraseña es obligatorio.');
        }

        $rol = isset($data['rol']) && trim((string)$data['rol']) !== ''
            ? trim((string)$data['rol'])
            : 'operador';

        if (!in_array($rol, self::ROLES_VALIDOS, true)) {
            throw new InvalidArgumentException(
                'Rol inválido. Valores permitidos: ' . implode(', ', self::ROLES_VALIDOS) . '.'
            );
        }

        $estado = isset($data['estado']) && trim((string)$data['estado']) !== ''
            ? trim((string)$data['estado'])
            : 'activo';

        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . '.'
            );
        }

        return [
            'nombre' => $nombre,
            'correo' => $correo,
            'usuario' => $usuario,
            'contrasena' => $contrasena,
            'rol' => $rol,
            'estado' => $estado,
        ];
    }

    public function validarActualizacionUsuario(array $data): array
    {
        $limpios = [];

        if (array_key_exists('nombre', $data)) {
            $nombre = trim((string)$data['nombre']);
            if ($nombre === '') {
                throw new InvalidArgumentException('El campo nombre no puede estar vacío.');
            }
            $limpios['nombre'] = $nombre;
        }

        if (array_key_exists('correo', $data)) {
            $correo = trim((string)$data['correo']);
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Debe proporcionar un correo electrónico válido.');
            }
            $limpios['correo'] = $correo;
        }

        if (array_key_exists('usuario', $data)) {
            $usuario = trim((string)$data['usuario']);
            if ($usuario === '') {
                throw new InvalidArgumentException('El campo usuario no puede estar vacío.');
            }
            $limpios['usuario'] = $usuario;
        }

        foreach (['contrasena', 'contraseña', 'password'] as $campoPass) {
            if (array_key_exists($campoPass, $data)) {
                $pass = (string)$data[$campoPass];
                if (trim($pass) === '') {
                    throw new InvalidArgumentException('La contraseña no puede estar vacía.');
                }
                $limpios['contrasena'] = $pass;
                break;
            }
        }

        if (array_key_exists('rol', $data)) {
            $rol = trim((string)$data['rol']);
            if (!in_array($rol, self::ROLES_VALIDOS, true)) {
                throw new InvalidArgumentException(
                    'Rol inválido. Valores permitidos: ' . implode(', ', self::ROLES_VALIDOS) . '.'
                );
            }
            $limpios['rol'] = $rol;
        }

        if (array_key_exists('estado', $data)) {
            $estado = trim((string)$data['estado']);
            if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
                throw new InvalidArgumentException(
                    'Estado inválido. Valores permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . '.'
                );
            }
            $limpios['estado'] = $estado;
        }

        if ($limpios === []) {
            throw new InvalidArgumentException('No se enviaron campos válidos para actualizar.');
        }

        return $limpios;
    }

    public function extraerToken(Request $request, ?array $bodyData = null): string
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '') {
            $serverParams = $request->getServerParams();
            $authHeader = $serverParams['HTTP_AUTHORIZATION']
                ?? $serverParams['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';
        }

        if ($authHeader !== '') {
            if (preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }
            return trim($authHeader);
        }

        $xToken = trim($request->getHeaderLine('X-Auth-Token'));
        if ($xToken !== '') {
            return $xToken;
        }

        if (is_array($bodyData) && isset($bodyData['token']) && is_string($bodyData['token'])) {
            return trim($bodyData['token']);
        }

        return '';
    }

    private function extraerPrimerCampo(array $data, array $claves, bool $trim = true): string
    {
        foreach ($claves as $clave) {
            if (array_key_exists($clave, $data) && $data[$clave] !== null) {
                $valor = (string)$data[$clave];
                return $trim ? trim($valor) : $valor;
            }
        }

        return '';
    }
}
