<?php
/**
 * public/index.php
 *
 * Front Controller — Punto de entrada único para la API.
 *
 * Responsabilidades:
 * - Cargar variables de entorno.
 * - Cargar configuración.
 * - Registrar el autoload.
 * - Crear Request y Response.
 * - Cargar las rutas.
 * - Ejecutar el Router.
 *
 * No debe contener:
 * - SQL.
 * - consultas PDO directas.
 * - CRUD.
 * - reglas de negocio.
 * - lógica específica de especialidades.
 * - autenticación.
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cargar variables de entorno
|--------------------------------------------------------------------------
*/

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    if ($envContent !== false) {
        foreach (explode("\n", $envContent) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                if (getenv($key) === false) {
                    $_ENV[$key] = $value;
                    putenv($key . '=' . $value);
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Cargar configuración general
|--------------------------------------------------------------------------
*/

$appConfig = require __DIR__ . '/../config/app.php';

/*
|--------------------------------------------------------------------------
| Autoload simple de clases App\
|--------------------------------------------------------------------------
*/

spl_autoload_register(function (string $class): void {

    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $prefixLength = strlen($prefix);

    if (strncmp($prefix, $class, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLength);

    $file = $baseDir
        . str_replace('\\', '/', $relativeClass)
        . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/*
|--------------------------------------------------------------------------
| Crear componentes principales
|--------------------------------------------------------------------------
*/

$request = new \App\Core\Request();
$response = new \App\Core\Response();

/*
|--------------------------------------------------------------------------
| Cargar rutas
|--------------------------------------------------------------------------
*/

$router = require __DIR__ . '/../routes/api.php';

/*
|--------------------------------------------------------------------------
| Ejecutar Router
|--------------------------------------------------------------------------
*/

$response = $router->dispatch($request, $response);

/*
|--------------------------------------------------------------------------
| Enviar respuesta
|--------------------------------------------------------------------------
*/

$response->send();