<?php
/**
 * app/Repositories/AuthRepository.php
 *
 * Acceso a datos para autenticación de usuarios.
 * Es la única capa de autenticación autorizada para utilizar SQL/PDO.
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class AuthRepository
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
     * Busca un usuario por username o email.
     * Utiliza prepared statements para evitar inyección SQL.
     *
     * @param string $credential Username o email
     *
     * @return array|null Usuario encontrado o null si no existe
     * @throws PDOException
     */
    public function findByUsernameOrEmail(string $credential): ?array
    {
        $sql = '
            SELECT
                id_usuario AS id,
                username,
                password_hash,
                email,
                activo,
                created_at,
                updated_at
            FROM usuario
            WHERE (username = :credential OR email = :credential)
              AND activo = true
        ';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':credential', $credential, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Obtiene los roles de un usuario por su id_usuario.
     * Utiliza prepared statements para evitar inyección SQL.
     *
     * @param int $userId Id del usuario
     *
     * @return array<int, string> Nombres de rol
     * @throws PDOException
     */
    public function findRolesByUserId(int $userId): array
    {
        $sql = '
            SELECT r.nombre
            FROM usuario_rol ur
            JOIN rol r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :user_id
            ORDER BY r.nombre
        ';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            throw $e;
        }
    }
}