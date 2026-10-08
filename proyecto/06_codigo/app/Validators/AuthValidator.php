<?php
/**
 * app/Validators/AuthValidator.php
 *
 * Validación de entrada para autenticación de usuarios.
 * Extrae la validación del Controller, devolviendo resultados
 * estructurados que el Controller traduce a respuestas HTTP.
 *
 * No contiene SQL ni PDO.
 * Arquitectura: Router → Controller → Validator/Service → Repository → Database
 */

declare(strict_types=1);

namespace App\Validators;

class AuthValidator
{
    /**
     * Valida el cuerpo de la petición para login (POST /api/v1/auth/login).
     *
     * @return array{valid: bool, credential?: string, password?: string, error?: array{code: string, message: string, field: string}}
     */
    public static function validateLogin(array $body): array
    {
        // credential requerido (username o email)
        if (!array_key_exists('credential', $body) || $body['credential'] === null) {
            return self::fail('FIELD_REQUIRED', "El campo 'credential' es requerido.", 'credential');
        }
        if (!is_string($body['credential'])) {
            return self::fail('INVALID_FIELD_TYPE', "El campo 'credential' debe ser texto.", 'credential');
        }
        $credential = trim($body['credential']);
        if ($credential === '') {
            return self::fail('FIELD_EMPTY', "El campo 'credential' no puede estar vacío.", 'credential');
        }
        if (mb_strlen($credential) > 255) {
            return self::fail('FIELD_TOO_LONG', "El campo 'credential' no puede exceder los 255 caracteres.", 'credential');
        }

        // password requerido
        if (!array_key_exists('password', $body) || $body['password'] === null) {
            return self::fail('FIELD_REQUIRED', "El campo 'password' es requerido.", 'password');
        }
        if (!is_string($body['password'])) {
            return self::fail('INVALID_FIELD_TYPE', "El campo 'password' debe ser texto.", 'password');
        }
        $password = $body['password']; // No trim para permitir espacios al inicio/fin si son intencionales
        if ($password === '') {
            return self::fail('FIELD_EMPTY', "El campo 'password' no puede estar vacío.", 'password');
        }
        if (mb_strlen($password) > 255) {
            return self::fail('FIELD_TOO_LONG', "El campo 'password' no puede exceder los 255 caracteres.", 'password');
        }

        return ['valid' => true, 'credential' => $credential, 'password' => $password];
    }

    /**
     * Helper para crear resultado de error.
     */
    private static function fail(string $code, string $message, string $field): array
    {
        return [
            'valid' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'field' => $field,
            ],
        ];
    }
}