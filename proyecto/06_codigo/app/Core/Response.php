<?php
/**
 * app/Core/Response.php
 *
 * Encapsula la respuesta HTTP saliente.
 * Proporciona métodos para enviar respuestas JSON normalizadas con códigos de estado.
 */

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [
        'Content-Type' => 'application/json; charset=UTF-8',
    ];
    private mixed $data = null;

    public function __construct()
    {
        // Configurar zona horaria por defecto
        date_default_timezone_set('UTC');
    }

    /**
     * Establece el código de estado HTTP.
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Agrega un header a la respuesta.
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Establece el cuerpo de la respuesta (se serializará a JSON).
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Envía la respuesta al cliente.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->data !== null) {
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Respuesta de éxito estandarizada.
     */
    public function success(mixed $data, string $message = 'Operación exitosa', int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setData([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ]);
        return $this;
    }

    /**
     * Respuesta de error estandarizada.
     */
    public function error(string $code, string $message, ?string $field = null, array $details = [], int $statusCode = 400): self
    {
        $this->setStatusCode($statusCode);
        $this->setData([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'field' => $field,
                'details' => $details,
            ],
            'message' => $message,
        ]);
        return $this;
    }

    /**
     * Respuesta 404 - Recurso no encontrado.
     */
    public function notFound(string $message = 'Recurso no encontrado'): self
    {
        return $this->error('RESOURCE_NOT_FOUND', $message, null, [], 404);
    }

    /**
     * Respuesta 500 - Error interno del servidor.
     */
    public function serverError(string $message = 'Error interno del servidor'): self
    {
        return $this->error('INTERNAL_ERROR', $message, null, [], 500);
    }

    /**
     * Respuesta 405 - Método no permitido.
     */
    public function methodNotAllowed(string $message = 'Método no permitido'): self
    {
        return $this->error('METHOD_NOT_ALLOWED', $message, null, [], 405);
    }
}