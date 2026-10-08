<?php
/**
 * app/Core/Database.php
 *
 * Gestiona la conexión PDO a la base de datos PostgreSQL.
 * Se encarga de crear y proporcionar la conexión de forma segura.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=\'--client_encoding=%s\'',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => [
                    'code'    => 'DATABASE_CONNECTION_ERROR',
                    'message' => 'No se pudo conectar a la base de datos.',
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Verifica que la conexión a la base de datos está activa.
     * Ejecuta un ping ligero (SELECT 1) sin exponer errores de credenciales.
     *
     * @return bool true si la conexión es válida, false en caso contrario
     */
    public function ping(): bool
    {
        try {
            $this->pdo->query('SELECT 1 AS ok');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function __clone(): void {}

    public function __wakeup(): void {}
}