<?php
/**
 * AulaCode - Autenticación y control de roles
 */

declare(strict_types=1);

/**
 * Indica si hay un usuario autenticado.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['rol']);
}

/**
 * Devuelve los datos de sesión del usuario actual.
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'        => (int) $_SESSION['user_id'],
        'usuario'   => (string) ($_SESSION['usuario'] ?? ''),
        'nombre'    => (string) ($_SESSION['nombre'] ?? ''),
        'apellidos' => (string) ($_SESSION['apellidos'] ?? ''),
        'rol'       => (string) ($_SESSION['rol'] ?? ''),
        'grupo_id'  => isset($_SESSION['grupo_id']) ? (int) $_SESSION['grupo_id'] : null,
    ];
}

/**
 * Nombre completo del usuario actual.
 */
function current_user_fullname(): string
{
    $user = current_user();
    if ($user === null) {
        return '';
    }

    return trim($user['nombre'] . ' ' . $user['apellidos']);
}

/**
 * Exige estar autenticado.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Debes iniciar sesión para continuar.');
        redirect('auth/login.php');
    }
}

/**
 * Exige rol de administrador/profesor.
 */
function require_admin(): void
{
    require_login();

    if (($_SESSION['rol'] ?? '') !== ROLE_ADMIN) {
        flash_set('error', 'No tienes permisos para acceder a esta sección.');
        redirect('alumno/dashboard.php');
    }
}

/**
 * Exige rol de alumno.
 */
function require_alumno(): void
{
    require_login();

    if (($_SESSION['rol'] ?? '') !== ROLE_ALUMNO) {
        flash_set('error', 'No tienes permisos para acceder a esta sección.');
        redirect('admin/dashboard.php');
    }
}

/**
 * Intenta autenticar un usuario. Devuelve true si tiene éxito.
 */
function attempt_login(string $usuario, string $password): bool
{
    $usuario = trim($usuario);

    if ($usuario === '' || $password === '') {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT id, nombre, apellidos, usuario, password, rol, grupo_id, estado
         FROM usuarios
         WHERE usuario = :usuario
         LIMIT 1'
    );
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    if ((int) $user['estado'] !== 1) {
        flash_set('error', 'Tu cuenta está desactivada. Contacta con el profesor.');
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['usuario']   = $user['usuario'];
    $_SESSION['nombre']    = $user['nombre'];
    $_SESSION['apellidos'] = $user['apellidos'];
    $_SESSION['rol']       = $user['rol'];
    $_SESSION['grupo_id']  = $user['grupo_id'] !== null ? (int) $user['grupo_id'] : null;

    $update = db()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
    $update->execute(['id' => $user['id']]);

    // Registrar presencia básica (para fases posteriores)
    try {
        $ping = db()->prepare(
            'INSERT INTO sesiones_activas (usuario_id, ultimo_ping, ip)
             VALUES (:usuario_id, NOW(), :ip)
             ON DUPLICATE KEY UPDATE ultimo_ping = NOW(), ip = VALUES(ip)'
        );
        $ping->execute([
            'usuario_id' => $user['id'],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        // No bloquear el login si la tabla aún no se usa
    }

    return true;
}

/**
 * Cierra la sesión del usuario.
 */
function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) {
        try {
            $stmt = db()->prepare('DELETE FROM sesiones_activas WHERE usuario_id = :id');
            $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        } catch (Throwable $e) {
            // Ignorar si no existe la tabla o falla la limpieza
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}
