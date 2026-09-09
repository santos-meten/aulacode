<?php
/**
 * aulacode/admin/estadisticas.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();

$promedio = $pdo->query('SELECT AVG(porcentaje) FROM resultados')->fetchColumn();
$promedio = $promedio !== null ? (float) $promedio : null;

$totalResultados = (int) $pdo->query('SELECT COUNT(*) FROM resultados')->fetchColumn();
$aprobados = (int) $pdo->query('SELECT COUNT(*) FROM resultados WHERE porcentaje >= 50')->fetchColumn();
$suspensos = (int) $pdo->query('SELECT COUNT(*) FROM resultados WHERE porcentaje < 50')->fetchColumn();

$pctAprobados = $totalResultados > 0 ? ($aprobados / $totalResultados) * 100 : null;
$pctSuspensos = $totalResultados > 0 ? ($suspensos / $totalResultados) * 100 : null;

$masDificil = $pdo->query(
    "SELECT e.titulo, AVG(r.porcentaje) AS media
     FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id
     INNER JOIN ejercicios e ON e.id = i.ejercicio_id
     GROUP BY e.id, e.titulo
     ORDER BY media ASC
     LIMIT 1"
)->fetch();

$mejorAlumno = $pdo->query(
    "SELECT u.nombre, u.apellidos, AVG(r.porcentaje) AS media
     FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id
     INNER JOIN usuarios u ON u.id = i.usuario_id
     GROUP BY u.id, u.nombre, u.apellidos
     ORDER BY media DESC
     LIMIT 1"
)->fetch();

$preguntaErrores = $pdo->query(
    "SELECT p.enunciado,
            SUM(CASE WHEN resp.es_correcta = 0 THEN 1 ELSE 0 END) AS errores,
            COUNT(resp.id) AS total
     FROM respuestas resp
     INNER JOIN preguntas p ON p.id = resp.pregunta_id
     WHERE resp.es_correcta IS NOT NULL
     GROUP BY p.id, p.enunciado
     ORDER BY errores DESC, total DESC
     LIMIT 1"
)->fetch();

$evolucion = $pdo->query(
    "SELECT DATE(i.finished_at) AS dia, AVG(r.porcentaje) AS media
     FROM resultados r
     INNER JOIN intentos i ON i.id = r.intento_id
     WHERE i.finished_at IS NOT NULL
     GROUP BY DATE(i.finished_at)
     ORDER BY dia ASC
     LIMIT 30"
)->fetchAll();

$pageTitle = 'Estadísticas';
$activeMenu = 'estadisticas';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Estadísticas del aula</h2>
        <p>Resumen de rendimiento de la clase.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Promedio de la clase</span>
        <strong class="stat-value"><?= e(format_percent($promedio)) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">% Aprobados (≥50%)</span>
        <strong class="stat-value"><?= e(format_percent($pctAprobados)) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">% Suspensos</span>
        <strong class="stat-value"><?= e(format_percent($pctSuspensos)) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Resultados registrados</span>
        <strong class="stat-value"><?= $totalResultados ?></strong>
    </article>
</section>

<section class="cards-sections">
    <article class="panel">
        <h3>Ejercicio más difícil</h3>
        <?php if ($masDificil): ?>
            <p><strong><?= e($masDificil['titulo']) ?></strong></p>
            <p class="muted">Media: <?= e(format_percent((float)$masDificil['media'])) ?></p>
        <?php else: ?>
            <p class="muted">Aún no hay datos suficientes.</p>
        <?php endif; ?>
    </article>
    <article class="panel">
        <h3>Mejor rendimiento</h3>
        <?php if ($mejorAlumno): ?>
            <p><strong><?= e($mejorAlumno['nombre'] . ' ' . $mejorAlumno['apellidos']) ?></strong></p>
            <p class="muted">Media: <?= e(format_percent((float)$mejorAlumno['media'])) ?></p>
        <?php else: ?>
            <p class="muted">Aún no hay datos suficientes.</p>
        <?php endif; ?>
    </article>
    <article class="panel">
        <h3>Pregunta con más errores</h3>
        <?php if ($preguntaErrores): ?>
            <p><?= e(mb_strimwidth($preguntaErrores['enunciado'], 0, 120, '…')) ?></p>
            <p class="muted"><?= (int)$preguntaErrores['errores'] ?> errores de <?= (int)$preguntaErrores['total'] ?> respuestas</p>
        <?php else: ?>
            <p class="muted">Aún no hay respuestas corregidas.</p>
        <?php endif; ?>
    </article>
    <article class="panel">
        <h3>Evolución de las notas</h3>
        <?php if (!$evolucion): ?>
            <p class="muted">Cuando haya ejercicios finalizados verás la evolución diaria.</p>
        <?php else: ?>
            <ul class="checklist">
                <?php foreach ($evolucion as $ev): ?>
                    <li>
                        <?= e(date('d/m/Y', strtotime($ev['dia']))) ?>:
                        <strong><?= e(format_percent((float)$ev['media'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
