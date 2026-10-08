<?php
/**
 * app/Core/Router.php
 *
 * Enrutador simple de aplicaciones.
 * Mapea rutas HTTP (método + ruta) a callbacks.
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private ?\Closure $notFoundHandler = null;
    private ?\Closure $methodNotAllowedHandler = null;

    /**
     * Agrega una ruta para un método HTTP específico.
     */
    public function addRoute(string $method, string $path, \Closure $handler): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
        return $this;
    }

    /**
     * Alinea rutas sin considerar mayúsculas/minúsculas.
     */
    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

    /**
     * Establece el handler para rutas no encontradas.
     */
    public function setNotFoundHandler(\Closure $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Establece el handler para métodos no permitidos.
     */
    public function setMethodNotAllowedHandler(\Closure $handler): self
    {
        $this->methodNotAllowedHandler = $handler;
        return $this;
    }

    /**
     * Maneja una petición y ejecuta el handler correspondiente.
     */
    public function dispatch(Request $request, Response $response): Response
    {
        $method = strtoupper($request->getMethod());
        $uri = $request->getPath();

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route['method'], $route['path'], $uri, $method)) {
                return $route['handler']($request, $response);
            }
        }

        // Check if route exists with different method
        foreach ($this->routes as $route) {
            if ($this->matchRoute('*', $route['path'], $uri, $method) && $route['method'] !== $method) {
                $handler = $this->methodNotAllowedHandler;
                return $handler ? $handler($request, $response) : $response->methodNotAllowed();
            }
        }

        $handler = $this->notFoundHandler;
        return $handler ? $handler($request, $response) : $response->notFound();
    }

    /**
     * Coincide una ruta con parámetros (ej. /api/v1/especialidades/{id}).
     */
    private function matchRoute(string $routeMethod, string $routePath, string $uri, string $requestMethod): bool
    {
        // Check method (unless it's a wildcard)
        if ($routeMethod !== '*' && $routeMethod !== $requestMethod) {
            return false;
        }

        // Convertir ruta con parámetros a expresión regular.
        $pattern = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '[^/]+', $routePath);
        $regex = '#^' . $pattern . '$#';

        return (bool) preg_match($regex, $uri);
    }
}