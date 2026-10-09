<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cargar variables de entorno
|--------------------------------------------------------------------------
*/

$envFile = dirname(__DIR__) . '/.env';

if (is_file($envFile)) {

    $lines = file(
        $envFile,
        FILE_IGNORE_NEW_LINES |
        FILE_SKIP_EMPTY_LINES
    );

    if ($lines !== false) {

        foreach ($lines as $line) {

            $line = trim($line);

            if (
                $line === ''
                || str_starts_with($line, '#')
                || !str_contains($line, '=')
            ) {
                continue;
            }

            [$key, $value] =
                explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if (getenv($key) === false) {

                $_ENV[$key] = $value;

                putenv(
                    $key . '=' . $value
                );
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Configuración PostgreSQL
|--------------------------------------------------------------------------
*/

return [

    'engine' =>
        getenv('DB_ENGINE')
        ?: 'postgresql',

    'host' =>
        getenv('DB_HOST')
        ?: 'localhost',

    'port' =>
        getenv('DB_PORT')
        ?: '5432',

    'name' =>
        getenv('DB_NAME')
        ?: 'hospital_citas_dev',

    'user' =>
        getenv('DB_USER')
        ?: 'postgres',

    'pass' =>
        getenv('DB_PASS')
        ?: '',

    'charset' =>
        getenv('DB_CHARSET')
        ?: 'utf8'
];