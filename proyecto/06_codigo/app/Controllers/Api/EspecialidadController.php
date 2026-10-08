<?php
/**
 * app/Controllers/Api/EspecialidadController.php
 *
 * Controlador API para especialidades.
 *
 * Endpoints implementados:
 * GET  /api/v1/especialidades
 * GET  /api/v1/especialidades/{id}
 * POST /api/v1/especialidades
 * PUT  /api/v1/especialidades/{id}
 * DELETE /api/v1/especialidades/{id}
 *
 * No contiene SQL ni utiliza PDO directamente.
 * Valida entrada usando EspecialidadValidator.
 * Arquitectura: Router → Controller → Validator/Service → Repository → Database
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\EspecialidadRepository;
use App\Services\EspecialidadService;
use App\Validators\EspecialidadValidator;
use InvalidArgumentException;
use PDOException;

class EspecialidadController
{
    /**
     * Construye el servicio respetando:
     *
     * Controller
     * → Service
     * → Repository
     * → Database/PDO
     */
    private function getService(): EspecialidadService
    {
        $repository = new EspecialidadRepository();

        return new EspecialidadService(
            $repository
        );
    }

    /**
     * GET /api/v1/especialidades
     */
    public function index(
        Request $request,
        Response $response
    ): Response {
        $params = EspecialidadValidator::validateListParams($request->getQueryParams());

        try {
            $service = $this->getService();

            $especialidades = $service->getAll(
                $params['page'],
                $params['limit'],
                $params['orderBy'],
                $params['search']
            );

            return $response->success(
                $especialidades,
                'Especialidades obtenidas correctamente.'
            );
        } catch (PDOException $e) {
            return $response->serverError(
                'Error interno del servidor'
            );
        }
    }

    /**
     * GET /api/v1/especialidades/{id}
     */
    public function show(
        Request $request,
        Response $response
    ): Response {
        $uri = $request->getUri();

        $parts = explode(
            '/',
            trim($uri, '/')
        );

        $idStr = (string) end($parts);

        $validation = EspecialidadValidator::validateId($idStr);
        if (!$validation['valid']) {
            $error = $validation['error'];
            return $response->error(
                $error['code'],
                $error['message'],
                $error['field'],
                [],
                400
            );
        }

        $id = $validation['id'];

        try {
            $service = $this->getService();

            $especialidad = $service->getById(
                $id
            );

            if ($especialidad === null) {
                return $response->notFound(
                    "No se encontró una especialidad activa con el identificador {$id}."
                );
            }

            return $response->success(
                $especialidad,
                'Especialidad obtenida correctamente.'
            );
        } catch (PDOException $e) {
            return $response->serverError(
                'Error interno del servidor'
            );
        }
    }

    /**
     * POST /api/v1/especialidades
     */
    public function create(
        Request $request,
        Response $response
    ): Response {
        try {
            $body = $request->getBodyParams();

            $validation = EspecialidadValidator::validateCreate($body);
            if (!$validation['valid']) {
                $error = $validation['error'];
                return $response->error(
                    $error['code'],
                    $error['message'],
                    $error['field'],
                    [],
                    400
                );
            }

            $service = $this->getService();

            $nuevaEspecialidad = $service->create(
                $validation['nombre'],
                $validation['descripcion']
            );

            return $response->success(
                $nuevaEspecialidad,
                'Especialidad creada correctamente.'
            )->setStatusCode(201);

        } catch (InvalidArgumentException $e) {
            return $response->error(
                'VALIDATION_ERROR',
                $e->getMessage(),
                null,
                [],
                400
            );
        } catch (PDOException $e) {
            return $response->serverError(
                'No se pudo guardar la especialidad en la base de datos.'
            );
        }
    }

    /**
     * PUT /api/v1/especialidades/{id}
     */
    public function update(
        Request $request,
        Response $response
    ): Response {
        $uri = $request->getUri();

        $parts = explode(
            '/',
            trim($uri, '/')
        );

        $idStr = (string) end($parts);

        $idValidation = EspecialidadValidator::validateId($idStr);
        if (!$idValidation['valid']) {
            $error = $idValidation['error'];
            return $response->error(
                $error['code'],
                $error['message'],
                $error['field'],
                [],
                400
            );
        }

        $id = $idValidation['id'];

        $body = $request->getBodyParams();

        $validation = EspecialidadValidator::validateUpdate($body);
        if (!$validation['valid']) {
            $error = $validation['error'];
            return $response->error(
                $error['code'],
                $error['message'],
                $error['field'],
                [],
                400
            );
        }

        try {
            $service = $this->getService();

            $especialidad = $service->update(
                $id,
                $validation['nombre'],
                $validation['descripcion']
            );

            if ($especialidad === []) {
                return $response->notFound(
                    "No existe una especialidad activa con el identificador {$id}."
                );
            }

            return $response->success(
                $especialidad,
                'Especialidad actualizada correctamente.'
            );

        } catch (InvalidArgumentException $e) {
            return $response->error(
                'VALIDATION_ERROR',
                $e->getMessage(),
                null,
                [],
                400
            );
        } catch (PDOException $e) {
            return $response->serverError(
                'No se pudo actualizar la especialidad en la base de datos.'
            );
        }
    }

    /**
     * DELETE /api/v1/especialidades/{id}
     */
    public function delete(
        Request $request,
        Response $response
    ): Response {
        $uri = $request->getUri();

        $parts = explode(
            '/',
            trim($uri, '/')
        );

        $idStr = (string) end($parts);

        $validation = EspecialidadValidator::validateId($idStr);
        if (!$validation['valid']) {
            $error = $validation['error'];
            return $response->error(
                $error['code'],
                $error['message'],
                $error['field'],
                [],
                400
            );
        }

        $id = $validation['id'];

        try {
            $service = $this->getService();

            $result = $service->softDelete($id);

            if ($result === []) {
                return $response->notFound(
                    "No existe una especialidad activa con el identificador {$id}."
                );
            }

            return $response->success(
                $result,
                "Especialidad inactivada correctamente. id={$id} (activo=false)."
            );

        } catch (PDOException $e) {
            return $response->serverError(
                'No se pudo inactivar la especialidad en la base de datos.'
            );
        }
    }
}