<?php
/**
 * aulacode/alumno/progreso.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_alumno();

$user = current_user();
$pdo = db();
$userId = (int) $user['id'];

$stmtPend = $pdo->prepare(
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
$stmtPend->execute(['uid' => $userId, 'gid' => $user['grupo_id'], 'uid2' => $userId]);
$pendientes = (int) $stmtPend->fetchColumn();

$stmtReal = $pdo->prepare(
    "SELECT COUNT(*) FROM intentos WHERE usuario_id = :id AND estado IN ('FINALIZADO','TIEMPO_AGOTADO')"
);
$stmtReal->execute(['id' => $userId]);
$realizados = (int) $stmtReal->fetchColumn();

$stmtProm = $pdo->prepare(
    'SELECT AVG(r.porcentaje) FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id WHERE i.usuario_id = :id'
);
$stmtProm->execute(['id' => $userId]);
$promedio = $stmtProm->fetchColumn();
$promedio = $promedio !== null ? (float) $promedio : null;

$stmtMejor = $pdo->prepare(
    'SELECT MAX(r.porcentaje) FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id WHERE i.usuario_id = :id'
);
$stmtMejor->execute(['id' => $userId]);
$mejor = $stmtMejor->fetchColumn();
$mejor = $mejor !== null ? (float) $mejor : null;

$hist = $pdo->prepare(
    "SELECT e.titulo, r.porcentaje, i.finished_at
     FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id
     INNER JOIN ejercicios e ON e.id = i.ejercicio_id
     WHERE i.usuario_id = :id
     ORDER BY i.finished_at DESC
     LIMIT 10"
);
$hist->execute(['id' => $userId]);
$historial = $hist->fetchAll();

$pageTitle = 'Mi progreso';
$activeMenu = 'progreso';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Mi progreso</h2>
        <p>Resumen de tu evolución en AulaCode.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Pendientes</span>
        <strong class="stat-value"><?= $pendientes ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Realizados</span>
        <strong class="stat-value"><?= $realizados ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Promedio</span>
        <strong class="stat-value"><?= e(format_percent($promedio)) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Mejor nota</span>
        <strong class="stat-value"><?= e(format_percent($mejor)) ?></strong>
    </article>
</section>

<section class="panel">
    <h3>Últimos resultados</h3>
    <?php if (!$historial): ?>
        <p class="muted">Todavía no hay historial de notas.</p>
    <?php else: ?>
        <ul class="checklist">
            <?php foreach ($historial as $h): ?>
                <li>
                    <strong><?= e($h['titulo']) ?></strong>
                    — <?= e(format_percent((float)$h['porcentaje'])) ?>
                    <span class="muted small">
                        (<?= e($h['finished_at'] ? date('d/m/Y', strtotime($h['finished_at'])) : '') ?>)
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
