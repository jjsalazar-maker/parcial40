<?php

use App\Middlewares\AuthMiddleware;
use App\Presentation\Repositories\ConductorRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('/conductores', function (RouteCollectorProxy $group) {
        $group->get('', [ConductorRepository::class, 'all']);
        $group->get('/documento/{documento}', [ConductorRepository::class, 'byDocumento']);
        $group->get('/licencia/{numero_licencia}', [ConductorRepository::class, 'byLicencia']);
        $group->get('/estado/{estado}', [ConductorRepository::class, 'byEstado']);
        $group->get('/{id:[0-9]+}', [ConductorRepository::class, 'find']);
        $group->post('', [ConductorRepository::class, 'create']);
        $group->put('/{id:[0-9]+}', [ConductorRepository::class, 'update']);
        $group->patch('/{id:[0-9]+}/estado', [ConductorRepository::class, 'changeEstado']);
        $group->delete('/{id:[0-9]+}', [ConductorRepository::class, 'delete']);
    })->add(new AuthMiddleware());
};
