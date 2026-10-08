<?php
/**
 * app/Controllers/Api/AuthController.php
 *
 * Controlador API para autenticación de usuarios.
 *
 * Endpoints implementados:
 * POST /api/v1/auth/login
 *
 * No contiene SQL ni utiliza PDO directamente.
 * Valida entrada usando AuthValidator.
 * Arquitectura: Router → Controller → Validator/Service → Repository → Database
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuthRepository;
use App\Services\AuthService;
use App\Validators\AuthValidator;
use PDOException;
use RuntimeException;

class AuthController
{
    /**
     * Construye el servicio respetando:
     *
     * Controller
     * → Service
     * → Repository
     * → Database/PDO
     */
    private function getService(): AuthService
    {
        $repository = new AuthRepository();

        return new AuthService($repository);
    }

    /**
     * POST /api/v1/auth/login
     *
     * Autentica a un usuario con credencial (username o email) y contraseña.
     *
     * - Credenciales incorrectas o usuario inexistente/inactivo → 401 genérico
     * - Cuerpo inválido → 400
     * - Error de base de datos → 500
     */
    public function login(
        Request $request,
        Response $response
    ): Response {
        $body = $request->getBodyParams();

        $validation = AuthValidator::validateLogin($body);
        if (!$validation['valid']) {
            $error = $validation['error'];
            return $response->error(
                $error['code'],
                $error['message'],
                $error['field'],
                [],
                400
            );
        }

        try {
            $service = $this->getService();

            $user = $service->login(
                $validation['credential'],
                $validation['password']
            );

            return $response->success(
                $user,
                'Autenticación correcta.'
            );

        } catch (PDOException $e) {
            // Error de base de datos: no exponer mensajes SQL
            return $response->serverError(
                'Error interno del servidor'
            );
        } catch (RuntimeException $e) {
            // Credenciales inválidas: no revelar si fue username o password
            return $response->error(
                'AUTH_FAILED',
                'Credenciales inválidas.',
                null,
                [],
                401
            );
        }
    }

    /**
     * GET /api/v1/auth/me
     *
     * Devuelve información del usuario autenticado en sesión.
     * - Sesión válida → 200 con id_usuario, username, roles
     * - Sin sesión → 401 AUTH_REQUIRED
     */
    public function me(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['auth'])) {
            return $response->error(
                'AUTH_REQUIRED',
                'Acceso no autorizado.',
                null,
                [],
                401
            );
        }

        $auth = $_SESSION['auth'];

        return $response->success([
            'id_usuario' => $auth['id_usuario'],
            'username'   => $auth['username'],
            'roles'      => $auth['roles'],
        ], 'Información del usuario');
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request, Response $response): Response
    {
        $service = $this->getService();
        $service->logout();

        return $response->success(
            null,
            'Sesión cerrada correctamente.'
        );
    }
}