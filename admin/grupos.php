<?php
/**
 * aulacode/admin/grupos.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errores[] = 'Token de seguridad no válido.';
    } else {
        $accion = (string) ($_POST['accion'] ?? 'crear');

        if ($accion === 'crear') {
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
            if ($nombre === '') {
                $errores[] = 'El nombre del grupo es obligatorio.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO grupos (nombre, descripcion, activo) VALUES (:n, :d, 1)'
                    );
                    $stmt->execute(['n' => $nombre, 'd' => $descripcion !== '' ? $descripcion : null]);
                    flash_set('success', 'Grupo creado.');
                    redirect('admin/grupos.php');
                } catch (PDOException $e) {
                    $errores[] = 'No se pudo crear el grupo (¿nombre duplicado?).';
                }
            }
        } elseif ($accion === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE grupos SET activo = IF(activo=1,0,1) WHERE id = :id')->execute(['id' => $id]);
            flash_set('success', 'Estado del grupo actualizado.');
            redirect('admin/grupos.php');
        }
    }
}

$grupos = $pdo->query(
    'SELECT g.*, (SELECT COUNT(*) FROM usuarios u WHERE u.grupo_id = g.id AND u.rol = \'ALUMNO\') AS total_alumnos
     FROM grupos g
     ORDER BY g.nombre'
)->fetchAll();

$pageTitle = 'Grupos';
$activeMenu = 'alumnos';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Grupos / cursos</h2>
        <p>Organiza a los alumnos por clase.</p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('admin/alumnos.php')) ?>">Volver a alumnos</a>
</section>

<section class="panel form-panel">
    <h3>Nuevo grupo</h3>
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" class="form-inline-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="crear">
        <input type="text" name="nombre" required maxlength="100" placeholder="Nombre (ej. 1º Informática)">
        <input type="text" name="descripcion" maxlength="255" placeholder="Descripción opcional">
        <button type="submit" class="btn btn-primary">Crear</button>
    </form>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Alumnos</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grupos as $g): ?>
                <tr>
                    <td><?= e($g['nombre']) ?></td>
                    <td><?= e($g['descripcion'] ?? '—') ?></td>
                    <td><?= (int)$g['total_alumnos'] ?></td>
                    <td>
                        <span class="badge <?= (int)$g['activo'] === 1 ? 'badge-success' : 'badge-muted' ?>">
                            <?= (int)$g['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                            <button class="btn btn-small btn-secondary" type="submit">
                                <?= (int)$g['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
