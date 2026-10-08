<?php
/**
 * app/Services/EspecialidadService.php
 *
 * Lógica de negocio para la entidad especialidad.
 * Orquesta entre Controller y Repository.
 * No contiene SQL ni utiliza PDO directamente.
 */

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EspecialidadRepository;
use InvalidArgumentException;
use PDOException;

class EspecialidadService
{
    private EspecialidadRepository $repository;

    public function __construct(
        EspecialidadRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Obtiene todas las especialidades activas.
     *
     * @throws PDOException
     */
    public function getAll(
        int $page,
        int $limit,
        string $orderBy,
        string $search
    ): array {
        return $this->repository->getAll(
            $page,
            $limit,
            $orderBy,
            $search
        );
    }

    /**
     * Obtiene una especialidad activa por ID.
     *
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
     * Crea una nueva especialidad.
     *
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function create(
        string $nombre,
        ?string $descripcion = null
    ): array {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException(
                "El campo 'nombre' no puede estar vacío."
            );
        }

        if (mb_strlen($nombre) > 255) {
            throw new InvalidArgumentException(
                "El campo 'nombre' no puede exceder los 255 caracteres."
            );
        }

        if ($descripcion !== null) {
            $descripcion = trim($descripcion);

            if ($descripcion === '') {
                $descripcion = null;
            }

            if (
                $descripcion !== null
                && mb_strlen($descripcion) > 1024
            ) {
                throw new InvalidArgumentException(
                    "El campo 'descripcion' no puede exceder los 1024 caracteres."
                );
            }
        }

        return $this->repository->create(
            $nombre,
            $descripcion
        );
    }

    /**
     * Actualiza una especialidad activa.
     *
     * Para PUT el nombre es obligatorio.
     *
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function update(
        int $id,
        string $nombre,
        ?string $descripcion = null
    ): array {
        if ($id <= 0) {
            return [];
        }

        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException(
                "El campo 'nombre' no puede estar vacío."
            );
        }

        if (mb_strlen($nombre) > 255) {
            throw new InvalidArgumentException(
                "El campo 'nombre' no puede exceder los 255 caracteres."
            );
        }

        if ($descripcion !== null) {
            $descripcion = trim($descripcion);

            if ($descripcion === '') {
                $descripcion = null;
            }

            if (
                $descripcion !== null
                && mb_strlen($descripcion) > 1024
            ) {
                throw new InvalidArgumentException(
                    "El campo 'descripcion' no puede exceder los 1024 caracteres."
                );
            }
        }

        $especialidad = $this->repository->update(
            $id,
            $nombre,
            $descripcion
        );

        if ($especialidad === null) {
            return [];
        }

        return $especialidad;
    }

    /**
     * Inactiva una especialidad (eliminación lógica).
     *
     * Establece activo = false y actualiza updated_at.
     * Nunca ejecuta DELETE FROM especialidad.
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

        $especialidad = $this->repository->softDelete($id);

        if ($especialidad === null) {
            return [];
        }

        return $especialidad;
    }
}