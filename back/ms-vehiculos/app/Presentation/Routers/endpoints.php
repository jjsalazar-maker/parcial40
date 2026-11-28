<?php

use App\Middlewares\AuthMiddleware;
use App\Presentation\Repositories\VehiculoRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('/vehiculos', function (RouteCollectorProxy $group) {
        $group->get('', [VehiculoRepository::class, 'all']);
        $group->get('/placa/{placa}', [VehiculoRepository::class, 'byPlaca']);
        $group->get('/estado/{estado}', [VehiculoRepository::class, 'byEstado']);
        $group->get('/tipo/{tipo}', [VehiculoRepository::class, 'byTipo']);
        $group->get('/{id:[0-9]+}', [VehiculoRepository::class, 'find']);
        $group->post('', [VehiculoRepository::class, 'create']);
        $group->put('/{id:[0-9]+}', [VehiculoRepository::class, 'update']);
        $group->patch('/{id:[0-9]+}/estado', [VehiculoRepository::class, 'changeEstado']);
        $group->delete('/{id:[0-9]+}', [VehiculoRepository::class, 'delete']);
    })->add(new AuthMiddleware());
};
