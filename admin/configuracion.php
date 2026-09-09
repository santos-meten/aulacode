<?php
/**
 * aulacode/admin/configuracion.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();
$user = current_user();
$errores = [];
$okMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errores[] = 'Token de seguridad no válido.';
    } else {
        $accion = (string) ($_POST['accion'] ?? '');

        if ($accion === 'password') {
            $actual = (string) ($_POST['password_actual'] ?? '');
            $nueva = (string) ($_POST['password_nueva'] ?? '');
            $nueva2 = (string) ($_POST['password_nueva2'] ?? '');

            $stmt = $pdo->prepare('SELECT password FROM usuarios WHERE id = :id');
            $stmt->execute(['id' => $user['id']]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($actual, (string) $hash)) {
                $errores[] = 'La contraseña actual no es correcta.';
            } elseif (strlen($nueva) < 6) {
                $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            } elseif ($nueva !== $nueva2) {
                $errores[] = 'Las contraseñas nuevas no coinciden.';
            } else {
                $upd = $pdo->prepare('UPDATE usuarios SET password = :p WHERE id = :id');
                $upd->execute([
                    'p' => password_hash($nueva, PASSWORD_DEFAULT),
                    'id' => $user['id'],
                ]);
                flash_set('success', 'Contraseña actualizada.');
                redirect('admin/configuracion.php');
            }
        }
    }
}

$pageTitle = 'Configuración';
$activeMenu = 'configuracion';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Configuración</h2>
        <p>Ajustes del profesor y mantenimiento básico.</p>
    </div>
</section>

<section class="cards-sections">
    <article class="panel form-panel">
        <h3>Cambiar mi contraseña</h3>
        <?php foreach ($errores as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="password">
            <label for="password_actual">Contraseña actual</label>
            <input type="password" id="password_actual" name="password_actual" required>
            <label for="password_nueva">Nueva contraseña</label>
            <input type="password" id="password_nueva" name="password_nueva" required minlength="6">
            <label for="password_nueva2">Repetir nueva contraseña</label>
            <input type="password" id="password_nueva2" name="password_nueva2" required minlength="6">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar contraseña</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <h3>Información del sistema</h3>
        <ul class="checklist">
            <li>Aplicación: <strong><?= e(APP_NAME) ?></strong> <?= e(APP_VERSION) ?></li>
            <li>Base de datos: <code><?= e(DB_NAME) ?></code></li>
            <li>Usuario conectado: <code><?= e($user['usuario']) ?></code></li>
            <li>PHP: <?= e(PHP_VERSION) ?></li>
        </ul>
    </article>

    <article class="panel">
        <h3>Copia de seguridad (phpMyAdmin)</h3>
        <ol class="steps">
            <li>Abre <code>http://localhost/phpmyadmin</code></li>
            <li>Selecciona la base <strong>aulacode</strong></li>
            <li>Pestaña <strong>Exportar</strong></li>
            <li>Método rápido, formato SQL</li>
            <li>Guarda el archivo en un lugar seguro</li>
        </ol>
    </article>

    <article class="panel">
        <h3>Acceso LAN del aula</h3>
        <p>Los alumnos deben entrar a:</p>
        <p><code>http://IP_DEL_PROFESOR/aulacode/</code></p>
        <p class="muted">Averigua tu IP con <code>ipconfig</code> y permite Apache en el firewall de Windows (red privada).</p>
    </article>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
