<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/global/database_config.php';

$cors = require __DIR__ . '/../app/Middlewares/cors.php';
$users_routers = require __DIR__ . '/../app/Presentation/Routers/endpoints.php';

$app = AppFactory::create();

// Detectar basePath automáticamente cuando se ejecuta bajo Apache/XAMPP en subcarpeta
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir !== '' && $scriptDir !== '/') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, $scriptDir . '/index.php') === 0) {
        $app->setBasePath($scriptDir . '/index.php');
    } else {
        $app->setBasePath($scriptDir);
    }
}

$app->addRoutingMiddleware();
$cors($app);

$users_routers($app);

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'service' => 'ms-auth',
        'status' => 'ok',
    ], JSON_UNESCAPED_UNICODE));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->addErrorMiddleware(true, true, true);

$app->run();
