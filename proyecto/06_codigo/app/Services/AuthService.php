<?php
/**
 * app/Services/AuthService.php
 *
 * Lógica de negocio para autenticación de usuarios.
 * Orquesta entre Controller y Repository.
 * No contiene SQL ni utiliza PDO directamente.
 *
 * Arquitectura: Router → Controller → Validator/Service → Repository → Database
 */

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuthRepository;
use RuntimeException;

class AuthService
{
    private AuthRepository $repository;

    public function __construct(AuthRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Autentica al usuario con credencial y contraseña.
     *
     * Credencial acepta username o email.
     *
     * @return array{id: int, username: string, email: string, roles: array<int, string>}
     *
     * @throws RuntimeException Credenciales inválidas o usuario inactivo
     */
    public function login(string $credential, string $password): array
    {
        $user = $this->repository->findByUsernameOrEmail($credential);

        if ($user === null) {
            throw new RuntimeException('Credenciales inválidas.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Credenciales inválidas.');
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            // Rehash futuro (no bloquea login; se aplicaría al actualizar contraseña)
        }

        // Obtener roles del usuario
        $roles = $this->repository->findRolesByUserId((int) $user['id']);

        // Sesión PHP: información mínima
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'id_usuario' => (int) $user['id'],
            'username'   => (string) $user['username'],
            'roles'      => array_values($roles),
        ];

        return [
            'id'       => (int) $user['id'],
            'username' => (string) $user['username'],
            'email'    => (string) $user['email'],
            'roles'    => array_values($roles),
        ];
    }

    /**
     * Cierra la sesión actual.
     * Reanuda sesión si es necesario, limpia $_SESSION, destruye la sesión e invalida la cookie.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        // Inutilizar la cookie de sesión si usa cookies
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }
}