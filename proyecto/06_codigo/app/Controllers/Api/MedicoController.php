<?php
/**
 * app/Controllers/Api/MedicoController.php
 *
 * Controlador API para médicos.
 *
 * CP-BACK-05:
 * - GET /api/v1/medicos
 * - GET /api/v1/medicos/{id}
 *
 * No contiene SQL ni utiliza PDO directamente.
 * Arquitectura:
 * Router → Controller → Validator/Service → Repository → Database
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\MedicoRepository;
use App\Services\MedicoService;
use App\Validators\MedicoValidator;
use PDOException;

class MedicoController
{
    /**
     * Construye el servicio de médicos.
     */
    private function getService(): MedicoService
    {
        $repository = new MedicoRepository();

        return new MedicoService($repository);
    }

    /**
     * GET /api/v1/medicos
     *
     * Lista únicamente médicos activos.
     */
    public function index(
        Request $request,
        Response $response
    ): Response {
        try {
            $service = $this->getService();

            $medicos = $service->getAll();

            return $response->success(
                $medicos,
                'Medicos obtenidos correctamente.'
            );

        } catch (PDOException $e) {
            return $response->serverError(
                'Error interno del servidor'
            );
        }
    }

    /**
     * GET /api/v1/medicos/{id}
     *
     * Obtiene un médico activo por ID.
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

        $validation = MedicoValidator::validateId($idStr);

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

            $medico = $service->getById($id);

            if ($medico === null) {
                return $response->notFound(
                    "No se encontro un medico activo con el identificador {$id}."
                );
            }

            return $response->success(
                $medico,
                'Medico obtenido correctamente.'
            );

        } catch (PDOException $e) {
            return $response->serverError(
                'Error interno del servidor'
            );
        }
    }
    /**
     * POST /api/v1/medicos
     *
     * Crea un nuevo medico.
     */
    public function create(
        Request $request,
        Response $response
    ): Response {
        $body = $request->getBodyParams();

        $validation = MedicoValidator::validateCreate($body);

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

            $medico = $service->create(
                $validation['nombre_completo'],
                $validation['numero_colegiado']
            );

            return $response->success(
                $medico,
                'Medico creado correctamente.'
            )->setStatusCode(201);

        } catch (PDOException $e) {

            // PostgreSQL: violacion de restriccion UNIQUE
            if ($e->getCode() === '23505') {
                return $response->error(
                    'DUPLICATE_RESOURCE',
                    'Ya existe un medico con ese numero colegiado.',
                    'numero_colegiado',
                    [],
                    409
                );
            }

            return $response->serverError(
                'No se pudo crear el medico en la base de datos.'
            );
        }
    }

    /**
     * PUT /api/v1/medicos/{id}
     *
     * Actualiza un medico activo existente.
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

        $idValidation = MedicoValidator::validateId($idStr);

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

        $validation = MedicoValidator::validateUpdate($body);

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

            $medico = $service->update(
                $id,
                $validation['nombre_completo'],
                $validation['numero_colegiado']
            );

            if ($medico === []) {
                return $response->notFound(
                    "No existe un medico activo con el identificador {$id}."
                );
            }

            return $response->success(
                $medico,
                'Medico actualizado correctamente.'
            );

        } catch (PDOException $e) {

            if ($e->getCode() === '23505') {
                return $response->error(
                    'DUPLICATE_RESOURCE',
                    'Ya existe un medico con ese numero colegiado.',
                    'numero_colegiado',
                    [],
                    409
                );
            }

            return $response->serverError(
                'No se pudo actualizar el medico en la base de datos.'
            );
        }
    }

    /**
     * DELETE /api/v1/medicos/{id}
     *
     * Inactiva un medico mediante eliminacion logica.
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

        $validation = MedicoValidator::validateId($idStr);

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

            $medico = $service->softDelete($id);

            if ($medico === []) {
                return $response->notFound(
                    "No existe un medico activo con el identificador {$id}."
                );
            }

            return $response->success(
                $medico,
                "Medico inactivado correctamente. id={$id} (activo=false)."
            );

        } catch (PDOException $e) {
            return $response->serverError(
                'No se pudo inactivar el medico en la base de datos.'
            );
        }
    }
}