<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;


    private function __construct()
    {
        $config =
            require __DIR__
            . '/../../config/database.php';


        if (
            $config['engine']
            !== 'postgresql'
        ) {
            throw new RuntimeException(
                'Motor de base de datos no permitido.'
            );
        }


        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=%s'",
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );


        try {

            $this->pdo =
                new PDO(
                    $dsn,
                    $config['user'],
                    $config['pass'],
                    [
                        PDO::ATTR_ERRMODE =>
                            PDO::ERRMODE_EXCEPTION,

                        PDO::ATTR_DEFAULT_FETCH_MODE =>
                            PDO::FETCH_ASSOC,

                        PDO::ATTR_EMULATE_PREPARES =>
                            false,

                        PDO::ATTR_PERSISTENT =>
                            false
                    ]
                );

        } catch (PDOException $exception) {

            throw new RuntimeException(
                'No fue posible conectar con PostgreSQL.'
            );
        }
    }


    public static function getInstance(): Database
    {
        if (self::$instance === null) {

            self::$instance =
                new self();
        }

        return self::$instance;
    }


    public function getPdo(): PDO
    {
        return $this->pdo;
    }


    public function ping(): bool
    {
        try {

            $statement =
                $this->pdo->query(
                    'SELECT 1 AS ok'
                );

            return
                $statement !== false
                && (int) $statement->fetchColumn()
                    === 1;

        } catch (PDOException) {

            return false;
        }
    }


    private function __clone(): void
    {
    }


    public function __wakeup(): void
    {
    }
}