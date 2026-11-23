<?php

namespace App\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class UsersController
{
    public function login(): bool;

    public function logout(): bool;
}
