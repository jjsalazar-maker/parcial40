<?php

namespace App\Controllers;

use App\Models\User;
use App\Validators\AuthValidator;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class UsersController implements UsersControllerInterface
{
    private AuthValidator $validator;

    public function __construct(?AuthValidator $validator = null)
    {
        $this->validator = $validator ?? new AuthValidator();
    }

    public function get_users(): Collection
    {
        return User::orderBy('id', 'asc')->get();
    }

    public function find_user_by_id(int $id): User
    {
        return User::findOrFail($id);
    }

    public function login(string $identificador, string $contrasena): User
    {
        $user = User::where('usuario', $identificador)
            ->orWhere('correo', $identificador)
            ->first();

        if (!$user || !$this->verificarContrasena($contrasena, (string)$user->contrasena)) {
            throw new RuntimeException('Credenciales incorrectas.', 401);
        }

        if ($user->estado !== 'activo') {
            throw new RuntimeException('El usuario se encuentra inactivo.', 403);
        }

        $user->token = bin2hex(random_bytes(16));
        $user->sesion_activa = true;
        $user->save();

        return $user;
    }

    public function logout(string $token): bool
    {
        $tokenLimpio = trim($token);
        if ($tokenLimpio === '') {
            throw new RuntimeException('Token no proporcionado.', 401);
        }

        $user = User::where('token', $tokenLimpio)
            ->where('sesion_activa', true)
            ->first();

        if (!$user) {
            throw new RuntimeException('Sesión inválida o ya cerrada.', 401);
        }

        $user->token = null;
        $user->sesion_activa = false;
        $user->save();

        return true;
    }

    public function validar_sesion(string $token): User
    {
        $tokenLimpio = trim($token);
        if ($tokenLimpio === '') {
            throw new RuntimeException('Token no proporcionado.', 401);
        }

        $user = User::where('token', $tokenLimpio)->first();
        if (!$user) {
            throw new RuntimeException('Token inválido o no existe.', 401);
        }

        if (!(bool)$user->sesion_activa) {
            throw new RuntimeException('La sesión no se encuentra activa.', 401);
        }

        if ($user->estado !== 'activo') {
            throw new RuntimeException('El usuario se encuentra inactivo.', 403);
        }

        return $user;
    }

    public function create(array $data): User
    {
        $limpios = $this->validator->validarCreacionUsuario($data);

        if (User::where('correo', $limpios['correo'])->exists()) {
            throw new RuntimeException('El correo ya se encuentra registrado.', 409);
        }

        if (User::where('usuario', $limpios['usuario'])->exists()) {
            throw new RuntimeException('El nombre de usuario ya se encuentra registrado.', 409);
        }

        $user = new User();
        $user->nombre = $limpios['nombre'];
        $user->correo = $limpios['correo'];
        $user->usuario = $limpios['usuario'];
        $user->contrasena = password_hash($limpios['contrasena'], PASSWORD_BCRYPT);
        $user->rol = $limpios['rol'];
        $user->token = null;
        $user->sesion_activa = false;
        $user->estado = $limpios['estado'];
        $user->save();

        return $user;
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $limpios = $this->validator->validarActualizacionUsuario($data);

        if (
            isset($limpios['correo']) &&
            User::where('correo', $limpios['correo'])->where('id', '!=', $id)->exists()
        ) {
            throw new RuntimeException('El correo ya se encuentra registrado por otro usuario.', 409);
        }

        if (
            isset($limpios['usuario']) &&
            User::where('usuario', $limpios['usuario'])->where('id', '!=', $id)->exists()
        ) {
            throw new RuntimeException('El nombre de usuario ya se encuentra registrado por otro usuario.', 409);
        }

        if (isset($limpios['nombre'])) {
            $user->nombre = $limpios['nombre'];
        }
        if (isset($limpios['correo'])) {
            $user->correo = $limpios['correo'];
        }
        if (isset($limpios['usuario'])) {
            $user->usuario = $limpios['usuario'];
        }
        if (isset($limpios['contrasena'])) {
            $user->contrasena = password_hash($limpios['contrasena'], PASSWORD_BCRYPT);
        }
        if (isset($limpios['rol'])) {
            $user->rol = $limpios['rol'];
        }
        if (isset($limpios['estado'])) {
            $user->estado = $limpios['estado'];
            if ($limpios['estado'] === 'inactivo') {
                $user->token = null;
                $user->sesion_activa = false;
            }
        }

        $user->save();

        return $user;
    }

    public function delete(int $id): User
    {
        $user = User::findOrFail($id);
        $user->delete();

        return $user;
    }

    private function verificarContrasena(string $plana, string $almacenada): bool
    {
        if (password_get_info($almacenada)['algo'] !== null) {
            return password_verify($plana, $almacenada);
        }

        return hash_equals($almacenada, $plana);
    }
}
