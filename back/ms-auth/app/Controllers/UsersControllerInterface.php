<?php

namespace App\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UsersControllerInterface
{
    public function get_users(): Collection;

    public function find_user_by_id(int $id): User;

    public function login(string $identificador, string $contrasena): User;

    public function logout(string $token): bool;

    public function validar_sesion(string $token): User;

    public function create(array $data): User;

    public function update(int $id, array $data): User;

    public function delete(int $id): User;
}
