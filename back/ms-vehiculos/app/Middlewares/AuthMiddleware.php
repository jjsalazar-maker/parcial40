<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use RuntimeException;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    private const DEFAULT_AUTH_URL = 'http://localhost/parcial40/back/ms-auth/public/validar-sesion';

    private string $authValidateUrl;

    /** @var callable|null */
    private $verifier;

    public function __construct(?string $authValidateUrl = null, ?callable $verifier = null)
    {
        $this->authValidateUrl = $authValidateUrl
            ?? (getenv('MS_AUTH_VALIDATE_URL') ?: self::DEFAULT_AUTH_URL);
        $this->verifier = $verifier;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        try {
            $token = $this->extraerToken($request);
            if ($token === '') {
                throw new RuntimeException('Token no proporcionado.', 401);
            }

            $authData = $this->verifier !== null
                ? ($this->verifier)($token)
                : $this->validarConMsAuth($token);

            $usuario = $authData['usuario'] ?? null;
            return $handler->handle($request->withAttribute('auth_user', $usuario));
        } catch (RuntimeException $ex) {
            $status = ($ex->getCode() >= 400 && $ex->getCode() < 600) ? $ex->getCode() : 401;
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

    private function validarConMsAuth(string $token): array
    {
        $ch = curl_init($this->authValidateUrl);
        if ($ch === false) {
            throw new RuntimeException('No se pudo inicializar la validación de sesión.', 503);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $rawBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false || $httpCode === 0) {
            throw new RuntimeException(
                'No se pudo conectar con ms-auth para validar la sesión: ' . $curlError,
                503
            );
        }

        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            throw new RuntimeException('Respuesta inválida del servicio de autenticación.', 502);
        }

        if ($httpCode !== 200 || empty($data['valid'])) {
            $mensaje = $data['error'] ?? 'Sesión no válida o expirada.';
            $status = ($httpCode >= 400 && $httpCode < 500) ? $httpCode : 401;
            throw new RuntimeException($mensaje, $status);
        }

        return $data;
    }

    private function extraerToken(Request $request): string
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '') {
            $serverParams = $request->getServerParams();
            $authHeader = $serverParams['HTTP_AUTHORIZATION']
                ?? $serverParams['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';
        }

        if ($authHeader !== '') {
            if (preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }
            return trim($authHeader);
        }

        return trim($request->getHeaderLine('X-Auth-Token'));
    }
}
