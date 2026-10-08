<?php
/**
 * config/database.php
 *
 * Parámetros de conexión a la base de datos PostgreSQL.
 * Todas las credenciales provienen de variables de entorno.
 * Nunca se hardcodean credenciales en este archivo.
 */

declare(strict_types=1);

return [
    'engine'   => getenv('DB_ENGINE') ?: 'postgresql',
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: '5432',
    'name'     => getenv('DB_NAME') ?: 'hospital_citas_dev',
    'user'     => getenv('DB_USER') ?: 'postgres',
    'pass'     => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8',
];