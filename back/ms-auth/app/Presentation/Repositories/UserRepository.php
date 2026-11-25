<?php

namespace App\Presentation\Repositories;

use App\Controllers\UsersController;
use App\Controllers\UsersControllerInterface;
use App\Validators\AuthValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class UserRepository
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

    public function all(Request $request, Response $response, array $args): Response
    {
        $rows = $this->controller->get_users();
        return $this->jsonResponse($response, $rows->toArray(), 200);
    }

    public function find(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $row = $this->controller->find_user_by_id($id);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Usuario no encontrado.'], 404);
        }
    }

    public function login(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $this->parseJsonBody($request);
            $credenciales = $this->validator->validarLogin($data);
            $user = $this->controller->login(
                $credenciales['identificador'],
                $credenciales['contrasena']
            );

            return $this->jsonResponse($response, [
                'mensaje' => 'Inicio de sesión exitoso.',
                'token' => $user->token,
                'sesion_activa' => (bool)$user->sesion_activa,
                'usuario' => $user->toArray(),
            ], 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        } catch (RuntimeException $ex) {
            $status = $this->httpStatusFromException($ex, 401);
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], $status);
        }
    }

    public function logout(Request $request, Response $response, array $args): Response
    {
        try {
            $bodyData = $this->parseOptionalJsonBody($request);
            $token = $this->validator->extraerToken($request, $bodyData);
            $this->controller->logout($token);

            return $this->jsonResponse($response, [
                'mensaje' => 'Sesión cerrada correctamente.',
                'sesion_activa' => false,
            ], 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        } catch (RuntimeException $ex) {
            $status = $this->httpStatusFromException($ex, 401);
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], $status);
        }
    }

    public function validarSesion(Request $request, Response $response, array $args): Response
    {
        try {
            $bodyData = $this->parseOptionalJsonBody($request);
            $token = $this->validator->extraerToken($request, $bodyData);
            $user = $this->controller->validar_sesion($token);

            return $this->jsonResponse($response, [
                'valid' => true,
                'autenticado' => true,
                'sesion_activa' => (bool)$user->sesion_activa,
                'usuario' => $user->toArray(),
            ], 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, [
                'valid' => false,
                'autenticado' => false,
                'error' => $ex->getMessage(),
            ], 400);
        } catch (RuntimeException $ex) {
            $status = $this->httpStatusFromException($ex, 401);
            return $this->jsonResponse($response, [
                'valid' => false,
                'autenticado' => false,
                'error' => $ex->getMessage(),
            ], $status);
        }
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $this->parseJsonBody($request);
            $row = $this->controller->create($data);
            return $this->jsonResponse($response, $row->toArray(), 201);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        } catch (RuntimeException $ex) {
            $status = $this->httpStatusFromException($ex, 409);
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], $status);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $data = $this->parseJsonBody($request);
            $row = $this->controller->update($id, $data);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Usuario no encontrado.'], 404);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        } catch (RuntimeException $ex) {
            $status = $this->httpStatusFromException($ex, 409);
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], $status);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $row = $this->controller->delete($id);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Usuario no encontrado.'], 404);
        }
    }

    private function parseJsonBody(Request $request): array
    {
        $body = $request->getBody()->getContents();
        if (trim($body) === '') {
            throw new InvalidArgumentException('El cuerpo de la petición JSON no puede estar vacío.');
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('JSON inválido en el cuerpo de la petición.');
        }

        return $data;
    }

    private function parseOptionalJsonBody(Request $request): ?array
    {
        $body = $request->getBody()->getContents();
        if (trim($body) === '') {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('JSON inválido en el cuerpo de la petición.');
        }

        return $data;
    }

    private function httpStatusFromException(RuntimeException $ex, int $default): int
    {
        $code = $ex->getCode();
        return ($code >= 400 && $code < 600) ? $code : $default;
    }

    private function jsonResponse(Response $response, mixed $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
