<?php
/**
 * app/Repositories/MedicoRepository.php
 *
 * Acceso a datos para médicos.
 * Es la única capa autorizada para utilizar SQL/PDO.
 *
 * CP-BACK-05:
 * - GET /api/v1/medicos
 * - GET /api/v1/medicos/{id}
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class MedicoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
    }

    /**
     * Obtiene todos los médicos activos.
     *
     * Incluye únicamente especialidades activas asociadas.
     *
     * @return array
     * @throws PDOException
     */
    public function getAll(): array
    {
        $sql = '
            SELECT
                m.id_medico,
                m.nombre_completo,
                m.numero_colegiado,
                COALESCE(
                    (
                        SELECT json_agg(
                            json_build_object(
                                \'id_especialidad\', e.id_especialidad,
                                \'nombre\', e.nombre
                            )
                            ORDER BY e.nombre
                        )
                        FROM medico_especialidad me
                        INNER JOIN especialidad e
                            ON e.id_especialidad = me.id_especialidad
                        WHERE me.id_medico = m.id_medico
                          AND e.activo = true
                    ),
                    \'[]\'::json
                ) AS especialidades
            FROM medico m
            WHERE m.activo = true
            ORDER BY m.nombre_completo ASC, m.id_medico ASC
        ';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($medicos as &$medico) {
                $medico['id_medico'] = (int) $medico['id_medico'];

                if (is_string($medico['especialidades'])) {
                    $medico['especialidades'] =
                        json_decode($medico['especialidades'], true) ?: [];
                }
            }

            unset($medico);

            return $medicos;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Obtiene un médico activo por su ID.
     *
     * Incluye únicamente especialidades activas asociadas.
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
                m.id_medico,
                m.nombre_completo,
                m.numero_colegiado,
                COALESCE(
                    (
                        SELECT json_agg(
                            json_build_object(
                                \'id_especialidad\', e.id_especialidad,
                                \'nombre\', e.nombre
                            )
                            ORDER BY e.nombre
                        )
                        FROM medico_especialidad me
                        INNER JOIN especialidad e
                            ON e.id_especialidad = me.id_especialidad
                        WHERE me.id_medico = m.id_medico
                          AND e.activo = true
                    ),
                    \'[]\'::json
                ) AS especialidades
            FROM medico m
            WHERE m.id_medico = :id
              AND m.activo = true
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->execute();

            $medico = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$medico) {
                return null;
            }

            $medico['id_medico'] = (int) $medico['id_medico'];

            if (is_string($medico['especialidades'])) {
                $medico['especialidades'] =
                    json_decode($medico['especialidades'], true) ?: [];
            }

            return $medico;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Crea un nuevo médico.
     *
     * El id_medico es generado automáticamente por PostgreSQL.
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
        $sql = '
            INSERT INTO medico (
                nombre_completo,
                numero_colegiado
            )
            VALUES (
                :nombre_completo,
                :numero_colegiado
            )
            RETURNING
                id_medico,
                nombre_completo,
                numero_colegiado,
                activo,
                created_at,
                updated_at
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':nombre_completo',
                $nombreCompleto,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':numero_colegiado',
                $numeroColegiado,
                PDO::PARAM_STR
            );

            $stmt->execute();

            $medico = $stmt->fetch(PDO::FETCH_ASSOC);

            $medico['id_medico'] = (int) $medico['id_medico'];
            $medico['activo'] = (bool) $medico['activo'];
            $medico['especialidades'] = [];

            return $medico;

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Actualiza un medico activo existente.
     *
     * @param int    $id
     * @param string $nombreCompleto
     * @param string $numeroColegiado
     *
     * @return array|null
     * @throws PDOException
     */
    public function update(
        int $id,
        string $nombreCompleto,
        string $numeroColegiado
    ): ?array {
        if ($id <= 0) {
            return null;
        }

        $sql = '
            UPDATE medico
            SET
                nombre_completo = :nombre_completo,
                numero_colegiado = :numero_colegiado,
                updated_at = now()
            WHERE id_medico = :id
            AND activo = true
            RETURNING
                id_medico,
                nombre_completo,
                numero_colegiado,
                activo,
                created_at,
                updated_at
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':nombre_completo',
                $nombreCompleto,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':numero_colegiado',
                $numeroColegiado,
                PDO::PARAM_STR
            );

            $stmt->execute();

            $medico = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$medico) {
                return null;
            }

            $medico['id_medico'] = (int) $medico['id_medico'];
            $medico['activo'] = (bool) $medico['activo'];
            $medico['especialidades'] = [];

            return $medico;

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Inactiva un medico.
     *
     * Eliminacion logica:
     * nunca ejecuta DELETE FROM medico.
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
            UPDATE medico
            SET
                activo = false,
                updated_at = now()
            WHERE id_medico = :id
            AND activo = true
            RETURNING
                id_medico,
                nombre_completo,
                numero_colegiado,
                activo,
                created_at,
                updated_at
        ';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->execute();

            $medico = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$medico) {
                return null;
            }

            $medico['id_medico'] = (int) $medico['id_medico'];
            $medico['activo'] = (bool) $medico['activo'];
            $medico['especialidades'] = [];

            return $medico;

        } catch (PDOException $e) {
            throw $e;
        }
    }
}
