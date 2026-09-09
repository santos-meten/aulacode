<?php
/**
 * aulacode/alumno/materiales.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_alumno();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM materiales WHERE id = :id AND publicado = 1 LIMIT 1');
    $stmt->execute(['id' => $id]);
    $material = $stmt->fetch();
    if (!$material) {
        flash_set('error', 'Material no encontrado.');
        redirect('alumno/materiales.php');
    }

    $pageTitle = $material['titulo'];
    $activeMenu = 'materiales';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <section class="toolbar">
        <div>
            <h2><?= e($material['titulo']) ?></h2>
            <p class="muted"><?= e($material['categoria']) ?></p>
        </div>
        <a class="btn btn-secondary" href="<?= e(url('alumno/materiales.php')) ?>">Volver</a>
    </section>
    <section class="panel content-article">
        <?= nl2br(e($material['contenido'])) ?>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$materiales = $pdo->query(
    'SELECT id, titulo, categoria, orden
     FROM materiales
     WHERE publicado = 1
     ORDER BY categoria, orden, titulo'
)->fetchAll();

$porCategoria = [];
foreach ($materiales as $m) {
    $porCategoria[$m['categoria']][] = $m;
}

$pageTitle = 'Material de estudio';
$activeMenu = 'materiales';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Material de estudio</h2>
        <p>Consulta la teoría de bases de datos, SQL y PHP.</p>
    </div>
</section>

<?php if (!$porCategoria): ?>
<section class="panel">
    <p class="empty-cell">Todavía no hay materiales publicados.</p>
</section>
<?php else: ?>
    <?php foreach ($porCategoria as $categoria => $items): ?>
    <section class="panel" style="margin-bottom:1rem;">
        <h3><?= e($categoria) ?></h3>
        <ul class="material-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="<?= e(url('alumno/materiales.php?id=' . (int)$item['id'])) ?>">
                        <?= e($item['titulo']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
