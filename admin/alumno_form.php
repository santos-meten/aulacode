<?php
/**
 * aulacode/admin/alumno_form.php
 * Crear / editar alumno
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$alumno = [
    'nombre' => '',
    'apellidos' => '',
    'usuario' => '',
    'grupo_id' => '',
    'estado' => 1,
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id AND rol = 'ALUMNO' LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Alumno no encontrado.');
        redirect('admin/alumnos.php');
    }
    $alumno = $row;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errores[] = 'Token de seguridad no válido.';
    } else {
        $alumno['nombre'] = trim((string) ($_POST['nombre'] ?? ''));
        $alumno['apellidos'] = trim((string) ($_POST['apellidos'] ?? ''));
        $alumno['usuario'] = trim((string) ($_POST['usuario'] ?? ''));
        $alumno['grupo_id'] = $_POST['grupo_id'] !== '' ? (int) $_POST['grupo_id'] : null;
        $alumno['estado'] = isset($_POST['estado']) ? 1 : 0;
        $password = (string) ($_POST['password'] ?? '');

        if ($alumno['nombre'] === '' || $alumno['apellidos'] === '' || $alumno['usuario'] === '') {
            $errores[] = 'Nombre, apellidos y usuario son obligatorios.';
        }

        if ($id === 0 && $password === '') {
            $errores[] = 'La contraseña es obligatoria para un alumno nuevo.';
        }

        if ($password !== '' && strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        $check = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = :u AND id <> :id LIMIT 1');
        $check->execute(['u' => $alumno['usuario'], 'id' => $id]);
        if ($check->fetch()) {
            $errores[] = 'Ese nombre de usuario ya existe.';
        }

        if (!$errores) {
            if ($id > 0) {
                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios
                         SET nombre=:nombre, apellidos=:apellidos, usuario=:usuario,
                             password=:password, grupo_id=:grupo_id, estado=:estado
                         WHERE id=:id AND rol=\'ALUMNO\''
                    );
                    $stmt->execute([
                        'nombre' => $alumno['nombre'],
                        'apellidos' => $alumno['apellidos'],
                        'usuario' => $alumno['usuario'],
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'grupo_id' => $alumno['grupo_id'],
                        'estado' => $alumno['estado'],
                        'id' => $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios
                         SET nombre=:nombre, apellidos=:apellidos, usuario=:usuario,
                             grupo_id=:grupo_id, estado=:estado
                         WHERE id=:id AND rol=\'ALUMNO\''
                    );
                    $stmt->execute([
                        'nombre' => $alumno['nombre'],
                        'apellidos' => $alumno['apellidos'],
                        'usuario' => $alumno['usuario'],
                        'grupo_id' => $alumno['grupo_id'],
                        'estado' => $alumno['estado'],
                        'id' => $id,
                    ]);
                }
                flash_set('success', 'Alumno actualizado correctamente.');
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (nombre, apellidos, usuario, password, rol, grupo_id, estado)
                     VALUES (:nombre, :apellidos, :usuario, :password, 'ALUMNO', :grupo_id, :estado)"
                );
                $stmt->execute([
                    'nombre' => $alumno['nombre'],
                    'apellidos' => $alumno['apellidos'],
                    'usuario' => $alumno['usuario'],
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'grupo_id' => $alumno['grupo_id'],
                    'estado' => $alumno['estado'],
                ]);
                flash_set('success', 'Alumno creado correctamente.');
            }
            redirect('admin/alumnos.php');
        }
    }
}

$grupos = grupos_activos();
$pageTitle = $id > 0 ? 'Editar alumno' : 'Nuevo alumno';
$activeMenu = 'alumnos';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2><?= e($pageTitle) ?></h2>
        <p>Los datos se usarán para el acceso al aula.</p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('admin/alumnos.php')) ?>">Volver</a>
</section>

<section class="panel form-panel">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($alumno['nombre']) ?>">

        <label for="apellidos">Apellidos</label>
        <input type="text" id="apellidos" name="apellidos" required maxlength="150" value="<?= e($alumno['apellidos']) ?>">

        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required maxlength="80" value="<?= e($alumno['usuario']) ?>" placeholder="ej. juan.perez">

        <label for="password">Contraseña<?= $id > 0 ? ' (dejar vacío para no cambiar)' : '' ?></label>
        <input type="password" id="password" name="password" maxlength="128" <?= $id === 0 ? 'required' : '' ?>>

        <label for="grupo_id">Curso / grupo</label>
        <select id="grupo_id" name="grupo_id">
            <option value="">— Sin grupo —</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= (string)$alumno['grupo_id'] === (string)$g['id'] ? 'selected' : '' ?>>
                    <?= e($g['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="checkbox-label">
            <input type="checkbox" name="estado" value="1" <?= (int)$alumno['estado'] === 1 ? 'checked' : '' ?>>
            Alumno activo
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
