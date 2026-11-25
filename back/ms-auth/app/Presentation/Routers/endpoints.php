<?php

use App\Middlewares\AuthMiddleware;
use App\Presentation\Repositories\UserRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Rutas públicas de autenticación y validación de sesión
    $app->post('/login', [UserRepository::class, 'login']);
    $app->post('/logout', [UserRepository::class, 'logout']);
    $app->get('/validar-sesion', [UserRepository::class, 'validarSesion']);
    $app->post('/validar-sesion', [UserRepository::class, 'validarSesion']);
    $app->post('/usuarios', [UserRepository::class, 'create']);

    // Rutas protegidas (requieren token válido y sesión activa)
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/usuarios', [UserRepository::class, 'all']);
        $group->get('/usuarios/{id:[0-9]+}', [UserRepository::class, 'find']);
        $group->put('/usuarios/{id:[0-9]+}', [UserRepository::class, 'update']);
        $group->delete('/usuarios/{id:[0-9]+}', [UserRepository::class, 'delete']);
    })->add(new AuthMiddleware());
};
