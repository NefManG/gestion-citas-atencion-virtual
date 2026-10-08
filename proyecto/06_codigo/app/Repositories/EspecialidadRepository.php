<?php
/**
 * app/Repositories/EspecialidadRepository.php
 *
 * Acceso a datos para la entidad especialidad.
 * Es la única capa de especialidad autorizada para utilizar SQL/PDO.
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class EspecialidadRepository
{
    private PDO $pdo;

    /**
     * El Repository obtiene la conexión desde la capa técnica Database.
     * De esta forma Controller y Service no acceden directamente a PDO.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
    }

    /**
     * Obtiene todas las especialidades activas con paginación,
     * ordenamiento y búsqueda.
     *
     * @param int    $page
     * @param int    $limit
     * @param string $orderBy
     * @param string $search
     *
     * @return array
     * @throws PDOException
     */
    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'id_asc',
        string $search = ''
    ): array {
        if ($page < 1) {
            $page = 1;
        }

        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        $orderField = 'id';
        $orderDirection = 'ASC';

        $parts = explode('_', $orderBy);

        if (count($parts) === 2) {
            $field = strtolower($parts[0]);
            $direction = strtoupper($parts[1]);

            $allowedFields = [
                'id',
                'nombre',
                'fecha_creacion'
            ];

            $allowedDirections = [
                'ASC',
                'DESC'
            ];

            if (
                in_array($field, $allowedFields, true)
                && in_array($direction, $allowedDirections, true)
            ) {
                $orderField = $field;
                $orderDirection = $direction;
            }
        }

        $where = [
            'activo = :activo'
        ];

        $params = [
            ':activo' => true
        ];

        if ($search !== '') {
            $where[] = 'nombre ILIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);

        $offset = ($page - 1) * $limit;

        $sql = sprintf(
            'SELECT
                id_especialidad AS id,
                nombre,
                descripcion,
                activo,
                created_at AS fecha_creacion,
                updated_at AS fecha_actualizacion
             FROM especialidad
             WHERE %s
             ORDER BY %s %s
             LIMIT :limit OFFSET :offset',
            $whereClause,
            $orderField,
            $orderDirection
        );

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':limit',
                $limit,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':offset',
                $offset,
                PDO::PARAM_INT
            );

            foreach ($params as $key => $value) {
                if (is_bool($value)) {
                    $stmt->bindValue(
                        $key,
                        $value,
                        PDO::PARAM_BOOL
                    );
                } else {
                    $stmt->bindValue(
                        $key,
                        $value,
                        PDO::PARAM_STR
                    );
                }
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Obtiene una especialidad activa por su ID.
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

        $sql = '
            SELECT
                id_especialidad AS id,
                nombre,
                descripcion,
                activo,
                created_at AS fecha_creacion,
                updated_at AS fecha_actualizacion
            FROM especialidad
            WHERE id_especialidad = :id
              AND activo = true
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Crea una nueva especialidad.
     *
     * @param string      $nombre
     * @param string|null $descripcion
     *
     * @return array
     * @throws PDOException
     */
    public function create(
        string $nombre,
        ?string $descripcion = null
    ): array {
        $sql = '
            INSERT INTO especialidad (
                nombre,
                descripcion
            )
            VALUES (
                :nombre,
                :descripcion
            )
            RETURNING
                id_especialidad AS id,
                nombre,
                descripcion,
                activo,
                created_at AS fecha_creacion,
                updated_at AS fecha_actualizacion
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':nombre',
                $nombre,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':descripcion',
                $descripcion,
                $descripcion !== null
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Actualiza una especialidad activa existente.
     *
     * Solo actualiza los campos no nulos proporcionados.
     *
     * @param int         $id
     * @param string|null $nombre
     * @param string|null $descripcion
     *
     * @return array|null
     * @throws PDOException
     */
    public function update(
        int $id,
        ?string $nombre = null,
        ?string $descripcion = null
    ): ?array {
        if ($id <= 0) {
            return null;
        }

        $fields = [];
        $params = [];

        if ($nombre !== null) {
            $fields[] = 'nombre = :nombre';
            $params[':nombre'] = $nombre;
        }

        if ($descripcion !== null) {
            $fields[] = 'descripcion = :descripcion';
            $params[':descripcion'] = $descripcion;
        }

        if (empty($fields)) {
            return null;
        }

        $fieldsClause = implode(', ', $fields);

        $sql = sprintf(
            'UPDATE especialidad
             SET %s,
                 updated_at = now()
             WHERE id_especialidad = :id
               AND activo = true
             RETURNING
                 id_especialidad AS id,
                 nombre,
                 descripcion,
                 activo,
                 created_at AS fecha_creacion,
                 updated_at AS fecha_actualizacion',
            $fieldsClause
        );

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            foreach ($params as $key => $value) {
                if (is_bool($value)) {
                    $stmt->bindValue(
                        $key,
                        $value,
                        PDO::PARAM_BOOL
                    );
                } else {
                    $stmt->bindValue(
                        $key,
                        $value,
                        PDO::PARAM_STR
                    );
                }
            }

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Inactiva una especialidad (eliminación lógica).
     * Nunca ejecuta DELETE FROM especialidad.
     * Solo establece activo = false y actualiza updated_at.
     *
     * @param int $id
     *
     * @return array|null
     * @throws PDOException
     */
    public function softDelete(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = '
            UPDATE especialidad
            SET activo = false,
                updated_at = now()
            WHERE id_especialidad = :id
              AND activo = true
            RETURNING
                id_especialidad AS id,
                nombre,
                descripcion,
                activo,
                created_at AS fecha_creacion,
                updated_at AS fecha_actualizacion
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}