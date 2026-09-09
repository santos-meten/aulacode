<?php
/**
 * aulacode/admin/dashboard.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();

$totalAlumnos = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios WHERE rol = 'ALUMNO' AND estado = 1"
)->fetchColumn();

$ejerciciosCreados = (int) $pdo->query('SELECT COUNT(*) FROM ejercicios')->fetchColumn();
$ejerciciosActivos = (int) $pdo->query(
    "SELECT COUNT(*) FROM ejercicios WHERE estado = 'PUBLICADO'"
)->fetchColumn();
$ejerciciosFinalizados = (int) $pdo->query(
    "SELECT COUNT(*) FROM ejercicios WHERE estado = 'CERRADO'"
)->fetchColumn();

$promedioClase = $pdo->query('SELECT AVG(porcentaje) FROM resultados')->fetchColumn();
$promedioClase = $promedioClase !== null ? (float) $promedioClase : null;

$conectados = (int) $pdo->query(
    'SELECT COUNT(*) FROM sesiones_activas WHERE ultimo_ping >= (NOW() - INTERVAL 15 MINUTE)'
)->fetchColumn();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$bodyClass = 'page-dashboard page-admin';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <h2>Bienvenido, <?= e(current_user()['nombre']) ?></h2>
    <p>Resumen del aula · <?= $conectados ?> sesión(es) activa(s) reciente(s).</p>
</section>

<section class="stats-grid">
    <a class="stat-card stat-link" href="<?= e(url('admin/alumnos.php')) ?>">
        <span class="stat-label">Total de alumnos</span>
        <strong class="stat-value"><?= $totalAlumnos ?></strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(url('admin/ejercicios.php')) ?>">
        <span class="stat-label">Ejercicios creados</span>
        <strong class="stat-value"><?= $ejerciciosCreados ?></strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(url('admin/ejercicios.php')) ?>">
        <span class="stat-label">Ejercicios activos</span>
        <strong class="stat-value"><?= $ejerciciosActivos ?></strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(url('admin/ejercicios.php')) ?>">
        <span class="stat-label">Ejercicios finalizados</span>
        <strong class="stat-value"><?= $ejerciciosFinalizados ?></strong>
    </a>
    <a class="stat-card stat-card-wide stat-link" href="<?= e(url('admin/estadisticas.php')) ?>">
        <span class="stat-label">Promedio de la clase</span>
        <strong class="stat-value"><?= e(format_percent($promedioClase)) ?></strong>
    </a>
</section>

<section class="cards-sections">
    <article class="panel">
        <h3>Accesos rápidos</h3>
        <div class="quick-links">
            <a class="btn btn-primary" href="<?= e(url('admin/alumno_form.php')) ?>">Nuevo alumno</a>
            <a class="btn btn-primary" href="<?= e(url('admin/ejercicio_form.php')) ?>">Nuevo ejercicio</a>
            <a class="btn btn-secondary" href="<?= e(url('admin/preguntas.php')) ?>">Preguntas</a>
            <a class="btn btn-secondary" href="<?= e(url('admin/resultados.php')) ?>">Resultados</a>
        </div>
    </article>
    <article class="panel">
        <h3>Consejo del aula</h3>
        <p>Publica un ejercicio, añade preguntas y asígnalo a un grupo. Los alumnos lo verán en <strong>Mis ejercicios</strong>.</p>
    </article>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
