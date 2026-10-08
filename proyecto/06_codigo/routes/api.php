<?php
/**
 * routes/api.php
 *
 * Definición de rutas de la API piloto.
 * Las rutas se registran en el Router centralizado.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$router = new Router();

// ---------------------------------------------------------------
// GET /api/v1/especialidades
// ---------------------------------------------------------------
$router->addRoute('GET', '/api/v1/especialidades', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\EspecialidadController();
    return $controller->index($request, $response);
});


//=---------------------------------------------------------------
// GET /api/v1/especialidades/{id}
//=---------------------------------------------------------------
$router->addRoute('GET', '/api/v1/especialidades/{id}', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\EspecialidadController();
    return $controller->show($request, $response);
});

//=---------------------------------------------------------------
// POST /api/v1/especialidades
//=---------------------------------------------------------------
$router->addRoute('POST', '/api/v1/especialidades', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\EspecialidadController();
    return $controller->create($request, $response);
});

//=---------------------------------------------------------------
// PUT /api/v1/especialidades/{id}
//=---------------------------------------------------------------
$router->addRoute('PUT', '/api/v1/especialidades/{id}', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\EspecialidadController();
    return $controller->update($request, $response);
});

//=---------------------------------------------------------------
// DELETE /api/v1/especialidades/{id}
//=---------------------------------------------------------------
$router->addRoute('DELETE', '/api/v1/especialidades/{id}', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\EspecialidadController();
    return $controller->delete($request, $response);
});

// ---------------------------------------------------------------
// POST /api/v1/auth/login
// ---------------------------------------------------------------
$router->addRoute('POST', '/api/v1/auth/login', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\AuthController();
    return $controller->login($request, $response);
});

// ---------------------------------------------------------------
// GET /api/v1/auth/me
// ---------------------------------------------------------------
$router->addRoute('GET', '/api/v1/auth/me', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\AuthController();
    return $controller->me($request, $response);
});

// ---------------------------------------------------------------
// POST /api/v1/auth/logout
// ---------------------------------------------------------------
$router->addRoute('POST', '/api/v1/auth/logout', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\AuthController();
    return $controller->logout($request, $response);
});

// ---------------------------------------------------------------
// GET /api/v1/health
// ---------------------------------------------------------------
$router->addRoute('GET', '/api/v1/health', function (Request $request, Response $response) {
    try {
        $db = Database::getInstance();

        if (!$db->ping()) {
            return $response->error(
                'DATABASE_ERROR',
                'No se pudo conectar a la base de datos.',
                null,
                [],
                500
            );
        }

        return $response->success([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => date('c'),
            'version' => '1.0.0',
        ], 'Servicio disponible');

    } catch (\Throwable $e) {
        return $response->error(
            'DATABASE_ERROR',
            'No se pudo conectar a la base de datos.',
            null,
            [],
            500
        );
    }
});

// ---------------------------------------------------------------
// Ruta no encontrada (404)
// ---------------------------------------------------------------
$router->setNotFoundHandler(function (Request $request, Response $response) {
    return $response->notFound(
        'La ruta ' . $request->getPath() . ' no existe.'
    );
});

// ---------------------------------------------------------------
// Ruta con método no permitido (405)
// ---------------------------------------------------------------
$router->setMethodNotAllowedHandler(function (Request $request, Response $response) {
    return $response->methodNotAllowed(
        'El método ' . $request->getMethod() . ' no está permitido para ' . $request->getPath() . '.'
    );
});

// ---------------------------------------------------------------
// GET /api/v1/medicos
// ---------------------------------------------------------------
$router->addRoute('GET', '/api/v1/medicos', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\MedicoController();
    return $controller->index($request, $response);
});

// ---------------------------------------------------------------
// GET /api/v1/medicos/{id}
// ---------------------------------------------------------------
$router->addRoute('GET', '/api/v1/medicos/{id}', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\MedicoController();
    return $controller->show($request, $response);
});

// ---------------------------------------------------------------
// POST /api/v1/medicos
// ---------------------------------------------------------------
$router->addRoute('POST', '/api/v1/medicos', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\MedicoController();
    return $controller->create($request, $response);
});

// ---------------------------------------------------------------
// PUT /api/v1/medicos/{id}
// ---------------------------------------------------------------
$router->addRoute('PUT', '/api/v1/medicos/{id}', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\MedicoController();
    return $controller->update($request, $response);
});

//----------------------------------------------------------------
// DELETE /api/v1/medicos/{id}
//----------------------------------------------------------------
$router->addRoute('DELETE', '/api/v1/medicos/{id}', function (Request $request, Response $response) {
    $controller = new \App\Controllers\Api\MedicoController();
    return $controller->delete($request, $response);
});
return $router;