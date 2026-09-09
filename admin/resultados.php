<?php
/**
 * aulacode/admin/resultados.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();

$filtroAlumno = trim((string) ($_GET['alumno'] ?? ''));
$filtroEjercicio = (int) ($_GET['ejercicio_id'] ?? 0);
$filtroGrupo = (int) ($_GET['grupo_id'] ?? 0);
$filtroEstado = trim((string) ($_GET['estado'] ?? ''));
$filtroFecha = trim((string) ($_GET['fecha'] ?? ''));

$sql = "SELECT i.id, i.started_at, i.finished_at, i.estado, i.puntuacion,
               u.nombre, u.apellidos, u.usuario, g.nombre AS grupo_nombre,
               e.titulo AS ejercicio_titulo, e.puntuacion_maxima,
               r.porcentaje
        FROM intentos i
        INNER JOIN usuarios u ON u.id = i.usuario_id
        INNER JOIN ejercicios e ON e.id = i.ejercicio_id
        LEFT JOIN grupos g ON g.id = u.grupo_id
        LEFT JOIN resultados r ON r.intento_id = i.id
        WHERE 1=1";
$params = [];

if ($filtroAlumno !== '') {
    $sql .= " AND (u.nombre LIKE :q OR u.apellidos LIKE :q OR u.usuario LIKE :q)";
    $params['q'] = '%' . $filtroAlumno . '%';
}
if ($filtroEjercicio > 0) {
    $sql .= ' AND e.id = :ejercicio_id';
    $params['ejercicio_id'] = $filtroEjercicio;
}
if ($filtroGrupo > 0) {
    $sql .= ' AND u.grupo_id = :grupo_id';
    $params['grupo_id'] = $filtroGrupo;
}
if ($filtroEstado !== '') {
    $sql .= ' AND i.estado = :estado';
    $params['estado'] = $filtroEstado;
}
if ($filtroFecha !== '') {
    $sql .= ' AND DATE(i.started_at) = :fecha';
    $params['fecha'] = $filtroFecha;
}

$sql .= ' ORDER BY i.started_at DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$filas = $stmt->fetchAll();

$ejercicios = $pdo->query('SELECT id, titulo FROM ejercicios ORDER BY titulo')->fetchAll();
$grupos = grupos_activos();
$estados = ['PENDIENTE', 'EN_CURSO', 'FINALIZADO', 'TIEMPO_AGOTADO', 'PENDIENTE_CORRECCION'];

$pageTitle = 'Resultados';
$activeMenu = 'resultados';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Resultados</h2>
        <p>Consulta intentos, estados y notas de la clase.</p>
    </div>
</section>

<section class="panel">
    <form method="get" class="filter-grid">
        <input type="text" name="alumno" placeholder="Alumno / usuario" value="<?= e($filtroAlumno) ?>">
        <select name="ejercicio_id">
            <option value="0">Todos los ejercicios</option>
            <?php foreach ($ejercicios as $ej): ?>
                <option value="<?= (int)$ej['id'] ?>" <?= $filtroEjercicio === (int)$ej['id'] ? 'selected' : '' ?>><?= e($ej['titulo']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="grupo_id">
            <option value="0">Todos los grupos</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= $filtroGrupo === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $est): ?>
                <option value="<?= e($est) ?>" <?= $filtroEstado === $est ? 'selected' : '' ?>><?= e(label_intento_estado($est)) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="fecha" value="<?= e($filtroFecha) ?>">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Ejercicio</th>
                    <th>Inicio</th>
                    <th>Finalización</th>
                    <th>Estado</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="6" class="empty-cell">No hay resultados con estos filtros. Cuando los alumnos realicen ejercicios aparecerán aquí.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $f): ?>
                <tr>
                    <td>
                        <?= e($f['apellidos'] . ', ' . $f['nombre']) ?>
                        <div class="muted small"><?= e($f['grupo_nombre'] ?? '') ?></div>
                    </td>
                    <td><?= e($f['ejercicio_titulo']) ?></td>
                    <td><?= e($f['started_at'] ? date('d/m/Y H:i', strtotime($f['started_at'])) : '—') ?></td>
                    <td><?= e($f['finished_at'] ? date('d/m/Y H:i', strtotime($f['finished_at'])) : '—') ?></td>
                    <td><span class="badge"><?= e(label_intento_estado($f['estado'])) ?></span></td>
                    <td>
                        <?php if ($f['puntuacion'] !== null): ?>
                            <?= e(format_score((float)$f['puntuacion'])) ?> / <?= e(format_score((float)$f['puntuacion_maxima'])) ?>
                            <?php if ($f['porcentaje'] !== null): ?>
                                <span class="muted small">(<?= e(format_percent((float)$f['porcentaje'])) ?>)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
