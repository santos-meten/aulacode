<?php
/**
 * aulacode/admin/alumnos.php
 * Listado y gestión de alumnos
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();

$sql = "SELECT u.id, u.nombre, u.apellidos, u.usuario, u.estado, u.grupo_id,
               g.nombre AS grupo_nombre,
               (SELECT COUNT(*) FROM intentos i WHERE i.usuario_id = u.id) AS total_ejercicios,
               (SELECT AVG(r.porcentaje)
                  FROM resultados r
                  INNER JOIN intentos i ON i.id = r.intento_id
                 WHERE i.usuario_id = u.id) AS promedio
        FROM usuarios u
        LEFT JOIN grupos g ON g.id = u.grupo_id
        WHERE u.rol = 'ALUMNO'
        ORDER BY u.apellidos, u.nombre";

$alumnos = $pdo->query($sql)->fetchAll();
$grupos = grupos_activos();

$pageTitle = 'Alumnos';
$activeMenu = 'alumnos';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Gestión de alumnos</h2>
        <p>Crea, edita y activa/desactiva cuentas del aula.</p>
    </div>
    <div class="toolbar-actions">
        <a class="btn btn-secondary" href="<?= e(url('admin/grupos.php')) ?>">Grupos</a>
        <a class="btn btn-primary" href="<?= e(url('admin/alumno_form.php')) ?>">Nuevo alumno</a>
    </div>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Usuario</th>
                    <th>Grupo</th>
                    <th>Estado</th>
                    <th>Ejercicios</th>
                    <th>Promedio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$alumnos): ?>
                <tr><td colspan="7" class="empty-cell">No hay alumnos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($alumnos as $a): ?>
                <tr>
                    <td><?= e($a['apellidos'] . ', ' . $a['nombre']) ?></td>
                    <td><code><?= e($a['usuario']) ?></code></td>
                    <td><?= e($a['grupo_nombre'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= (int)$a['estado'] === 1 ? 'badge-success' : 'badge-muted' ?>">
                            <?= (int)$a['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td><?= (int) $a['total_ejercicios'] ?></td>
                    <td><?= e(format_percent($a['promedio'] !== null ? (float)$a['promedio'] : null)) ?></td>
                    <td class="actions">
                        <a class="btn btn-small" href="<?= e(url('admin/alumno_form.php?id=' . (int)$a['id'])) ?>">Editar</a>
                        <form method="post" action="<?= e(url('admin/alumno_accion.php')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <input type="hidden" name="accion" value="toggle">
                            <button type="submit" class="btn btn-small btn-secondary">
                                <?= (int)$a['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                        <form method="post" action="<?= e(url('admin/alumno_accion.php')) ?>" class="inline-form" onsubmit="return confirm('¿Eliminar este alumno?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
