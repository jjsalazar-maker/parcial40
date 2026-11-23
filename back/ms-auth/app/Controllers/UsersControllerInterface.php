<?php

namespace App\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UsersControllerInterface
{
    public function login(): bool;

    public function logout(): bool;
 
}