<?php
/**
 * aulacode/auth/login.php
 * Pantalla de inicio de sesión
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';

// Si ya está autenticado, ir a su panel
if (is_logged_in()) {
    if (($_SESSION['rol'] ?? '') === ROLE_ADMIN) {
        redirect('admin/dashboard.php');
    }
    redirect('alumno/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Token de seguridad no válido. Recarga la página e inténtalo de nuevo.';
    } else {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($usuario === '' || $password === '') {
            $error = 'Introduce usuario y contraseña.';
        } elseif (attempt_login($usuario, $password)) {
            if (($_SESSION['rol'] ?? '') === ROLE_ADMIN) {
                redirect('admin/dashboard.php');
            }
            redirect('alumno/dashboard.php');
        } else {
            if ($error === null && empty($_SESSION['flash'])) {
                $error = 'Usuario o contraseña incorrectos.';
            } else {
                $flash = flash_get();
                $error = $flash['message'] ?? 'Usuario o contraseña incorrectos.';
            }
        }
    }
}

$pageTitle = 'Iniciar sesión';
$bodyClass = 'page-login';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="login-layout">
    <div class="login-visual" aria-hidden="true">
        <div class="login-visual-inner">
            <p class="brand-eyebrow">Laboratorio de aula</p>
            <h2 class="brand-hero"><?= e(APP_NAME) ?></h2>
            <p class="brand-lead"><?= e(APP_TAGLINE) ?></p>
            <ul class="login-features">
                <li>Aprende teoría</li>
                <li>Practica SQL y PHP</li>
                <li>Evalúa tu progreso</li>
            </ul>
        </div>
    </div>

    <div class="login-panel">
        <form method="post" action="<?= e(url('auth/login.php')) ?>" class="login-form" autocomplete="on">
            <h1>Iniciar sesión</h1>
            <p class="form-subtitle">Accede con tu usuario del aula</p>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <?= csrf_field() ?>

            <label for="usuario">Usuario</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                required
                autofocus
                maxlength="80"
                value="<?= e($_POST['usuario'] ?? '') ?>"
                placeholder="ej. alumno1"
            >

            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                maxlength="128"
                placeholder="••••••••"
            >

            <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>

            <p class="login-hint">
                El sistema detecta automáticamente si eres profesor o alumno.
            </p>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
