<?php

namespace App\Middlewares;

use App\Controllers\UsersController;
use App\Controllers\UsersControllerInterface;
use App\Validators\AuthValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use RuntimeException;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    private UsersControllerInterface $controller;
    private AuthValidator $validator;

    public function __construct(
        ?UsersControllerInterface $controller = null,
        ?AuthValidator $validator = null
    ) {
        $this->validator = $validator ?? new AuthValidator();
        $this->controller = $controller ?? new UsersController($this->validator);
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        try {
            $token = $this->validator->extraerToken($request);
            $user = $this->controller->validar_sesion($token);

            return $handler->handle($request->withAttribute('auth_user', $user));
        } catch (RuntimeException $ex) {
            $status = ($ex->getCode() >= 400 && $ex->getCode() < 500) ? $ex->getCode() : 401;
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'autenticado' => false,
                'error' => $ex->getMessage(),
            ], JSON_UNESCAPED_UNICODE));

            return $response
                ->withStatus($status)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
