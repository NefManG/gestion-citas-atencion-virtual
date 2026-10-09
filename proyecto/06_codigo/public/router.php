<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/index.php';
    return true;
}

/*
|--------------------------------------------------------------------------
| Archivos estáticos
|--------------------------------------------------------------------------
*/

$file = __DIR__ . $uri;

if (is_file($file)) {
    return false;
}

/*
|--------------------------------------------------------------------------
| Rutas Frontend
|--------------------------------------------------------------------------
*/

$routes = [
    '/'       => __DIR__ . '/login/index.php',
    '/login'  => __DIR__ . '/login/index.php',
    '/login/' => __DIR__ . '/login/index.php',

    '/dashboard'  => __DIR__ . '/dashboard/index.php',
    '/dashboard/'  => __DIR__ . '/dashboard/index.php',

    '/especialidades'     => __DIR__ . '/especialidades/index.php',
    '/especialidades/'    => __DIR__ . '/especialidades/index.php',

    '/medicos'            => __DIR__ . '/medicos/index.php',
    '/medicos/'           => __DIR__ . '/medicos/index.php',

];

if (isset($routes[$uri])) {
    require $routes[$uri];
    return true;
}

/*
|--------------------------------------------------------------------------
| 404
|--------------------------------------------------------------------------
*/

http_response_code(404);

echo '<h1>404</h1>';
echo '<p>Página no encontrada.</p>';

return true;