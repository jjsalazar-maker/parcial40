<?php

namespace App\Presentation\Repositories;

use App\Controllers\VehiculosController;
use App\Controllers\VehiculosControllerInterface;
use App\Validators\VehiculoValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class VehiculoRepository
{
    private VehiculosControllerInterface $controller;

    public function __construct(?VehiculosControllerInterface $controller = null)
    {
        $this->controller = $controller ?? new VehiculosController(new VehiculoValidator());
    }

    public function all(Request $request, Response $response, array $args): Response
    {
        try {
            $filtros = $request->getQueryParams();
            $rows = $this->controller->get_vehiculos($filtros);
            return $this->jsonResponse($response, $rows->toArray(), 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function find(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $row = $this->controller->find_vehiculo($id);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Vehículo no encontrado.'], 404);
        }
    }

    public function byPlaca(Request $request, Response $response, array $args): Response
    {
        try {
            $placa = (string)($args['placa'] ?? '');
            $row = $this->controller->find_by_placa($placa);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Vehículo no encontrado con esa placa.'], 404);
        }
    }

    public function byEstado(Request $request, Response $response, array $args): Response
    {
        try {
            $estado = (string)($args['estado'] ?? '');
            $rows = $this->controller->get_by_estado($estado);
            return $this->jsonResponse($response, $rows->toArray(), 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function byTipo(Request $request, Response $response, array $args): Response
    {
        try {
            $tipo = (string)($args['tipo'] ?? '');
            $rows = $this->controller->get_by_tipo($tipo);
            return $this->jsonResponse($response, $rows->toArray(), 200);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $this->parseJsonBody($request);
            $row = $this->controller->create_vehiculo($data);
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
            $row = $this->controller->update_vehiculo($id, $data);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Vehículo no encontrado.'], 404);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        } catch (RuntimeException $ex) {
            $status = $this->httpStatusFromException($ex, 409);
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], $status);
        }
    }

    public function changeEstado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $data = $this->parseJsonBody($request);

            if (!array_key_exists('estado', $data)) {
                throw new InvalidArgumentException('El campo estado es obligatorio.');
            }

            $row = $this->controller->change_estado($id, (string)$data['estado']);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Vehículo no encontrado.'], 404);
        } catch (InvalidArgumentException $ex) {
            return $this->jsonResponse($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $row = $this->controller->delete_vehiculo($id);
            return $this->jsonResponse($response, $row->toArray(), 200);
        } catch (ModelNotFoundException $ex) {
            return $this->jsonResponse($response, ['error' => 'Vehículo no encontrado.'], 404);
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
