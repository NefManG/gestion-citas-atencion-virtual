<?php
/**
 * app/Services/MedicoService.php
 *
 * Lógica de negocio para médicos.
 * Orquesta entre Controller y Repository.
 * No contiene SQL ni utiliza PDO directamente.
 *
 * CP-BACK-05:
 * - GET /api/v1/medicos
 * - GET /api/v1/medicos/{id}
 */

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MedicoRepository;
use PDOException;

class MedicoService
{
    private MedicoRepository $repository;

    public function __construct(
        MedicoRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Obtiene todos los médicos activos.
     *
     * @return array
     * @throws PDOException
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtiene un médico activo por ID.
     *
     * @param int $id
     *
     * @return array|null
     * @throws PDOException
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->getById($id);
    }
    /**
     * Crea un nuevo medico.
     *
     * @param string $nombreCompleto
     * @param string $numeroColegiado
     *
     * @return array
     * @throws PDOException
     */
    public function create(
    string $nombreCompleto,
    string $numeroColegiado
    ): array {
        $nombreCompleto = trim($nombreCompleto);
        $numeroColegiado = trim($numeroColegiado);

        return $this->repository->create(
            $nombreCompleto,
            $numeroColegiado
        );
    }

    /**
     * Actualiza un medico activo.
     *
     * @param int    $id
     * @param string $nombreCompleto
     * @param string $numeroColegiado
     *
     * @return array
     * @throws PDOException
     */
    public function update(
        int $id,
        string $nombreCompleto,
        string $numeroColegiado
    ): array {
        if ($id <= 0) {
            return [];
        }

        $nombreCompleto = trim($nombreCompleto);
        $numeroColegiado = trim($numeroColegiado);

        $medico = $this->repository->update(
            $id,
            $nombreCompleto,
            $numeroColegiado
        );

        if ($medico === null) {
            return [];
        }

        return $medico;
    }

    /**
     * Inactiva un medico mediante eliminacion logica.
     *
     * @param int $id
     *
     * @return array
     * @throws PDOException
    */
    public function softDelete(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $medico = $this->repository->softDelete($id);

        if ($medico === null) {
            return [];
        }

        return $medico;
    }
}