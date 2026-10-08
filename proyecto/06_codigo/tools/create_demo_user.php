<?php
/**
 * tools/create_demo_user.php
 *
 * Crea un usuario de demostración compatible con el esquema real de PostgreSQL.
 *
 * Uso:
 *   php tools/create_demo_user.php --username=<nombre> --email=<email> --password=<password> [--role=<role>]
 *
 * Variables de entorno alternativas:
 *   DEMO_USER_USERNAME, DEMO_USER_EMAIL, DEMO_USER_PASSWORD, DEMO_USER_ROLE
 *
 * Es seguro para ejecutar más de una vez: si el usuario ya existe, no lo duplica.
 *
 * Reglas de seguridad:
 * - La contraseña se almacena con password_hash(), nunca en texto plano.
 * - No expone credenciales ni traces de error en la salida.
 * - Utiliza prepared statements en todas las operaciones.
 * - No modifica el esquema de base de datos.
 */

declare(strict_types=1);

// Evitar que cualquier detalle de error de PHP se imprima en stdout/stderr.
ini_set('display_errors', '0');
error_reporting(0);

/* Cargar variables de entorno (igual que public/index.php).
 * - Lee proyecto/06_codigo/.env si existe.
 * - Solo establece variables que no estén ya definidas externamente.
 * - No sobrescribe variables de entorno ya existentes. */
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    if ($envContent !== false) {
        foreach (explode("\n", $envContent) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                if (getenv($key) === false) {
                    $_ENV[$key] = $value;
                    putenv($key . '=' . $value);
                }
            }
        }
    }
}

// ---------- 1. Resolución de parámetros ----------
// Orden de preferencia: argumento CLI > variable de entorno > null.
$opts = getopt('', ['username:', 'email:', 'password:', 'role:']);

$username = $opts['username'] ?? getenv('DEMO_USER_USERNAME') ?: null;
$email    = $opts['email']    ?? getenv('DEMO_USER_EMAIL')    ?: null;
$password = $opts['password'] ?? getenv('DEMO_USER_PASSWORD') ?: null;
$roleName = $opts['role']     ?? getenv('DEMO_USER_ROLE')     ?: 'paciente';

if (!$username || !$email || !$password) {
    fwrite(STDERR, 'Error: faltan parámetros obligatorios.' . PHP_EOL);
    fwrite(STDERR, 'Uso: php tools/create_demo_user.php --username=<nombre> --email=<email> --password=<password> [--role=<role>]' . PHP_EOL);
    fwrite(STDERR, 'Alternativa: DEMO_USER_USERNAME, DEMO_USER_EMAIL, DEMO_USER_PASSWORD, DEMO_USER_ROLE' . PHP_EOL);
    exit(1);
}

// ---------- 2. Conexión PDO (reutiliza la conexión existente de Database.php) ----------
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getInstance()->getPdo();
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error de conexión a la base de datos.' . PHP_EOL);
    exit(1);
}

// ---------- 3. Operación atómica ----------
$pdo->beginTransaction();

try {
    // a) Evitar duplicados: búsqueda única por username o email.
    $stmt = $pdo->prepare('SELECT id_usuario FROM usuario WHERE username = :username OR email = :email');
    $stmt->execute([':username' => $username, ':email' => $email]);
    $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($existing !== false) {
        $pdo->rollBack();
        echo "El usuario '{$username}' ya existe (id_usuario: {$existing['id_usuario']}). No se duplicó." . PHP_EOL;
        exit(0);
    }

    // b) Hash de contraseña con password_hash (NUNCA en texto plano).
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new \RuntimeException('No se pudo generar el hash de la contraseña.');
    }

    // c) Crear el usuario con el esquema real de la tabla usuario.
    $stmt = $pdo->prepare('
        INSERT INTO usuario (
            username,
            password_hash,
            email,
            activo,
            created_at,
            updated_at
        ) VALUES (
            :username,
            :password_hash,
            :email,
            true,
            now(),
            now()
        )
        RETURNING id_usuario
    ');
    $stmt->execute([
        ':username'      => $username,
        ':password_hash' => $passwordHash,
        ':email'         => $email,
    ]);

    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new \RuntimeException('No se pudo recuperar el identificador del usuario insertado.');
    }
    $userId = (int) $row['id_usuario'];

    // d) Asignar rol si el rol existe en el esquema (no se crea si falta).
    $stmt = $pdo->prepare('SELECT id_rol FROM rol WHERE nombre = :nombre');
    $stmt->execute([':nombre' => $roleName]);
    $roleRow = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($roleRow === false) {
        $pdo->rollBack();
        echo "Advertencia: el rol '{$roleName}' no existe en la base de datos. El usuario se creó sin asignación de rol." . PHP_EOL;
        exit(0);
    }

    $roleId = (int) $roleRow['id_rol'];

    // El PRIMARY KEY de usuario_rol es (id_usuario, id_rol), por eso esta
    // operación puede reintentarse sin error en ejecuciones repetidas.
    $stmt = $pdo->prepare('
        INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion)
        VALUES (:id_usuario, :id_rol, now())
    ');
    $stmt->execute([
        ':id_usuario' => $userId,
        ':id_rol'     => $roleId,
    ]);

    $pdo->commit();

    echo "Usuario de demostración creado correctamente." . PHP_EOL;
    echo "  id_usuario: {$userId}" . PHP_EOL;
    echo "  username:   {$username}" . PHP_EOL;
    echo "  email:      {$email}" . PHP_EOL;
    echo "  rol:        {$roleName} (id_rol: {$roleId})" . PHP_EOL;
    echo "  password_hash: almacenado con seguridad (longitud: " . strlen($passwordHash) . " caracteres)" . PHP_EOL;

    exit(0);

} catch (\Throwable $e) {
    $pdo->rollBack();
    // Registro interno del error, sin exponerlo al usuario.
    error_log('create_demo_user.php: ' . $e->getMessage());
    fwrite(STDERR, 'Error al crear el usuario de demostración.' . PHP_EOL);
    exit(1);
}
