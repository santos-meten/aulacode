<?php
/**
 * aulacode/alumno/dashboard.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_alumno();

$user = current_user();
$pdo = db();
$userId = (int) $user['id'];

$stmtPendientes = $pdo->prepare(
    "SELECT COUNT(*) FROM ejercicios e
     WHERE e.estado = 'PUBLICADO'
       AND (
         NOT EXISTS (SELECT 1 FROM asignaciones a WHERE a.ejercicio_id = e.id)
         OR EXISTS (
           SELECT 1 FROM asignaciones a
           WHERE a.ejercicio_id = e.id
             AND (a.usuario_id = :uid OR (a.grupo_id IS NOT NULL AND a.grupo_id = :gid))
         )
       )
       AND NOT EXISTS (
         SELECT 1 FROM intentos i
         WHERE i.ejercicio_id = e.id AND i.usuario_id = :uid2
           AND i.estado IN ('FINALIZADO','TIEMPO_AGOTADO','PENDIENTE_CORRECCION')
       )"
);
$stmtPendientes->execute(['uid' => $userId, 'gid' => $user['grupo_id'], 'uid2' => $userId]);
$pendientes = (int) $stmtPendientes->fetchColumn();

$stmtRealizados = $pdo->prepare(
    "SELECT COUNT(*) FROM intentos WHERE usuario_id = :id AND estado IN ('FINALIZADO','TIEMPO_AGOTADO')"
);
$stmtRealizados->execute(['id' => $userId]);
$realizados = (int) $stmtRealizados->fetchColumn();

$stmtPromedio = $pdo->prepare(
    'SELECT AVG(r.porcentaje)
     FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id
     WHERE i.usuario_id = :id'
);
$stmtPromedio->execute(['id' => $userId]);
$promedio = $stmtPromedio->fetchColumn();
$promedio = $promedio !== null ? (float) $promedio : null;

$stmtMejor = $pdo->prepare(
    'SELECT MAX(r.porcentaje)
     FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id
     WHERE i.usuario_id = :id'
);
$stmtMejor->execute(['id' => $userId]);
$mejor = $stmtMejor->fetchColumn();
$mejor = $mejor !== null ? (float) $mejor : null;

$grupoNombre = null;
if (!empty($user['grupo_id'])) {
    $stmtGrupo = $pdo->prepare('SELECT nombre FROM grupos WHERE id = :id LIMIT 1');
    $stmtGrupo->execute(['id' => $user['grupo_id']]);
    $grupoNombre = $stmtGrupo->fetchColumn() ?: null;
}

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$bodyClass = 'page-dashboard page-alumno';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <h2>Bienvenido, <?= e($user['nombre']) ?></h2>
    <p>
        <?php if ($grupoNombre): ?>
            Grupo: <strong><?= e($grupoNombre) ?></strong> ·
        <?php endif; ?>
        Tu espacio de aprendizaje, práctica y evaluación.
    </p>
</section>

<section class="stats-grid">
    <a class="stat-card stat-link" href="<?= e(url('alumno/ejercicios.php')) ?>">
        <span class="stat-label">Ejercicios pendientes</span>
        <strong class="stat-value"><?= $pendientes ?></strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(url('alumno/resultados.php')) ?>">
        <span class="stat-label">Ejercicios realizados</span>
        <strong class="stat-value"><?= $realizados ?></strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(url('alumno/progreso.php')) ?>">
        <span class="stat-label">Promedio</span>
        <strong class="stat-value"><?= e(format_percent($promedio)) ?></strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(url('alumno/progreso.php')) ?>">
        <span class="stat-label">Mejor puntuación</span>
        <strong class="stat-value"><?= e(format_percent($mejor)) ?></strong>
    </a>
</section>

<section class="cards-sections">
    <a class="panel panel-link" href="<?= e(url('alumno/ejercicios.php')) ?>">
        <h3>Mis ejercicios</h3>
        <p>Consulta las actividades publicadas para ti.</p>
    </a>
    <a class="panel panel-link" href="<?= e(url('alumno/resultados.php')) ?>">
        <h3>Mis resultados</h3>
        <p>Revisa tus notas e historial.</p>
    </a>
    <a class="panel panel-link" href="<?= e(url('alumno/progreso.php')) ?>">
        <h3>Mi progreso</h3>
        <p>Sigue tu evolución en el aula.</p>
    </a>
    <a class="panel panel-link" href="<?= e(url('alumno/materiales.php')) ?>">
        <h3>Material de estudio</h3>
        <p>Teoría de bases de datos, SQL y PHP.</p>
    </a>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
