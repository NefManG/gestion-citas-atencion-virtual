<?php
/**
 * app/Validators/EspecialidadValidator.php
 *
 * Validación de entrada para la entidad especialidad.
 * Extrae la validación del Controller, devolviendo resultados
 * estructurados que el Controller traduce a respuestas HTTP.
 *
 * No contiene SQL ni PDO.
 * Arquitectura: Router → Controller → Validator/Service → Repository → Database
 */

declare(strict_types=1);

namespace App\Validators;

class EspecialidadValidator
{
    /**
     * Valida un identificador de ruta (path parameter).
     *
     * @return array{valid: bool, id?: int, error?: array{code: string, message: string, field: string}}
     */
    public static function validateId(string $idStr): array
    {
        $idStr = (string) $idStr;
        if (!ctype_digit($idStr) || (int) $idStr <= 0) {
            return [
                'valid' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETER',
                    'message' => 'El identificador debe ser un número entero positivo.',
                    'field' => 'id',
                ],
            ];
        }
        return [
            'valid' => true,
            'id' => (int) $idStr,
        ];
    }

    /**
     * Valida el cuerpo de la petición para creación (POST).
     *
     * @return array{valid: bool, nombre?: string, descripcion?: string|null, error?: array{code: string, message: string, field: string}}
     */
    public static function validateCreate(array $body): array
    {
        // nombre requerido
        if (!array_key_exists('nombre', $body) || $body['nombre'] === null) {
            return self::fail('FIELD_REQUIRED', "El campo 'nombre' es requerido.", 'nombre');
        }
        if (!is_string($body['nombre'])) {
            return self::fail('INVALID_FIELD_TYPE', "El campo 'nombre' debe ser texto.", 'nombre');
        }
        $nombre = trim($body['nombre']);
        if ($nombre === '') {
            return self::fail('FIELD_EMPTY', "El campo 'nombre' no puede estar vacío.", 'nombre');
        }
        if (mb_strlen($nombre) > 255) {
            return self::fail('FIELD_TOO_LONG', "El campo 'nombre' no puede exceder los 255 caracteres.", 'nombre');
        }

        // descripcion opcional
        $descripcion = null;
        if (array_key_exists('descripcion', $body) && $body['descripcion'] !== null) {
            if (!is_string($body['descripcion'])) {
                return self::fail('INVALID_FIELD_TYPE', "El campo 'descripcion' debe ser texto.", 'descripcion');
            }
            $descripcion = trim($body['descripcion']);
            if ($descripcion === '') {
                $descripcion = null;
            }
            if ($descripcion !== null && mb_strlen($descripcion) > 1024) {
                return self::fail('FIELD_TOO_LONG', "El campo 'descripcion' no puede exceder los 1024 caracteres.", 'descripcion');
            }
        }

        return ['valid' => true, 'nombre' => $nombre, 'descripcion' => $descripcion];
    }

    /**
     * Valida el cuerpo de la petición para actualización (PUT).
     * En PUT el nombre es obligatorio (igual que POST).
     *
     * @return array{valid: bool, nombre?: string, descripcion?: string|null, error?: array{code: string, message: string, field: string}}
     */
    public static function validateUpdate(array $body): array
    {
        // Reutiliza la misma lógica que create
        return self::validateCreate($body);
    }

    /**
     * Valida parámetros de consulta para listado (GET /especialidades).
     *
     * @return array{page: int, limit: int, orderBy: string, search: string}
     */
    public static function validateListParams(array $query): array
    {
        $page = (int) ($query['pagina'] ?? 1);
        $limit = (int) ($query['limite'] ?? 10);
        $orderBy = (string) ($query['ordenar_por'] ?? 'id_asc');
        $search = (string) ($query['buscar'] ?? '');

        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        // Validar ordenar_por
        $allowedFields = ['id', 'nombre', 'fecha_creacion'];
        $allowedDirections = ['ASC', 'DESC'];
        $parts = explode('_', $orderBy);
        if (count($parts) === 2) {
            $field = strtolower($parts[0]);
            $direction = strtoupper($parts[1]);
            if (!in_array($field, $allowedFields, true) || !in_array($direction, $allowedDirections, true)) {
                $orderBy = 'id_asc';
            }
        } else {
            $orderBy = 'id_asc';
        }

        return [
            'page' => $page,
            'limit' => $limit,
            'orderBy' => $orderBy,
            'search' => $search,
        ];
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