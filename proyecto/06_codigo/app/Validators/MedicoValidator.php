<?php
/**
 * app/Validators/MedicoValidator.php
 *
 * Validacion de entrada para medicos.
 *
 * CP-BACK-05:
 * - GET /api/v1/medicos/{id}
 */

declare(strict_types=1);

namespace App\Validators;

class MedicoValidator
{
    /**
     * Valida un identificador de medico recibido por ruta.
     *
     * @return array{
     *   valid: bool,
     *   id?: int,
     *   error?: array{
     *     code: string,
     *     message: string,
     *     field: string
     *   }
     * }
     */
    public static function validateId(string $idStr): array
    {
        $idStr = (string) $idStr;

        if (!ctype_digit($idStr) || (int) $idStr <= 0) {
            return [
                'valid' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETER',
                    'message' => 'El identificador debe ser un numero entero positivo.',
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
     * Valida el cuerpo para crear un medico.
     *
     * @param array $body
     *
     * @return array
     */
    public static function validateCreate(array $body): array
    {
        // nombre_completo requerido
        if (
            !array_key_exists('nombre_completo', $body)
            || $body['nombre_completo'] === null
        ) {
            return self::fail(
                'FIELD_REQUIRED',
                "El campo 'nombre_completo' es requerido.",
                'nombre_completo'
            );
        }

        if (!is_string($body['nombre_completo'])) {
            return self::fail(
                'INVALID_FIELD_TYPE',
                "El campo 'nombre_completo' debe ser texto.",
                'nombre_completo'
            );
        }

        $nombreCompleto = trim($body['nombre_completo']);

        if ($nombreCompleto === '') {
            return self::fail(
                'FIELD_EMPTY',
                "El campo 'nombre_completo' no puede estar vacio.",
                'nombre_completo'
            );
        }

        if (mb_strlen($nombreCompleto) > 200) {
            return self::fail(
                'FIELD_TOO_LONG',
                "El campo 'nombre_completo' no puede exceder los 200 caracteres.",
                'nombre_completo'
            );
        }

        // numero_colegiado requerido
        if (
            !array_key_exists('numero_colegiado', $body)
            || $body['numero_colegiado'] === null
        ) {
            return self::fail(
                'FIELD_REQUIRED',
                "El campo 'numero_colegiado' es requerido.",
                'numero_colegiado'
            );
        }

        if (!is_string($body['numero_colegiado'])) {
            return self::fail(
                'INVALID_FIELD_TYPE',
                "El campo 'numero_colegiado' debe ser texto.",
                'numero_colegiado'
            );
        }

        $numeroColegiado = trim($body['numero_colegiado']);

        if ($numeroColegiado === '') {
            return self::fail(
                'FIELD_EMPTY',
                "El campo 'numero_colegiado' no puede estar vacio.",
                'numero_colegiado'
            );
        }

        if (mb_strlen($numeroColegiado) > 30) {
            return self::fail(
                'FIELD_TOO_LONG',
                "El campo 'numero_colegiado' no puede exceder los 30 caracteres.",
                'numero_colegiado'
            );
        }

        return [
            'valid' => true,
            'nombre_completo' => $nombreCompleto,
            'numero_colegiado' => $numeroColegiado,
        ];
    }

    /**
     * Construye una respuesta de validacion fallida.
     */
    private static function fail(
        string $code,
        string $message,
        string $field
    ): array {
        return [
            'valid' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'field' => $field,
            ],
        ];
    }

    /**
     * Valida el cuerpo para actualizar un medico.
     *
     * En PUT ambos campos son obligatorios.
     *
     * @param array $body
     *
     * @return array
     */
    public static function validateUpdate(array $body): array
    {
        return self::validateCreate($body);
    }
}