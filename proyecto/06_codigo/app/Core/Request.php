<?php
/**
 * app/Core/Request.php
 *
 * Encapsula el manejo de la petición HTTP entrante.
 * Proporciona acceso seguro a método, ruta, query params, body JSON y headers.
 */

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $bodyParams;
    private array $headers;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->normalizeUri($_SERVER['REQUEST_URI'] ?? '/');
        $this->queryParams = $_GET ?? [];
        $this->headers = $this->parseHeaders();
        $this->bodyParams = $this->parseBody();
    }

    /**
     * Normaliza la URI eliminando query string y decodificando.
     */
    private function normalizeUri(string $uri): string
    {
        // Separar query string
        $parts = explode('?', $uri, 2);
        $path = $parts[0] ?? '/';
        return urldecode($path);
    }

    /**
     * Extrae headers HTTP de $_SERVER.
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $header = strtolower(str_replace('_', '-', $key));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Parsea el cuerpo de la petición (JSON).
     */
    private function parseBody(): array
    {
        $contentType = $this->headers['content-type'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            return is_array($decoded) ? $decoded : [];
        }

        // Para form data, usar $_POST
        if (str_contains($contentType, 'application/x-www-form-urlencoded')
            || str_contains($contentType, 'multipart/form-data')) {
            return $_POST ?? [];
        }

        return [];
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBodyParams(): array
    {
        return $this->bodyParams;
    }

    public function getBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains($this->getHeader('content-type', ''), 'application/json');
    }

    public function getPath(): string
    {
        return $this->uri;
    }
}