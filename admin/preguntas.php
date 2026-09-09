<?php
/**
 * aulacode/admin/preguntas.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();
$ejercicioId = isset($_GET['ejercicio_id']) ? (int) $_GET['ejercicio_id'] : 0;

$ejercicios = $pdo->query('SELECT id, titulo FROM ejercicios ORDER BY titulo')->fetchAll();

if ($ejercicioId === 0 && $ejercicios) {
    $ejercicioId = (int) $ejercicios[0]['id'];
}

$preguntas = [];
$ejercicioActual = null;

if ($ejercicioId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM ejercicios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $ejercicioId]);
    $ejercicioActual = $stmt->fetch();

    if ($ejercicioActual) {
        $q = $pdo->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM opciones o WHERE o.pregunta_id = p.id) AS total_opciones
             FROM preguntas p
             WHERE p.ejercicio_id = :id
             ORDER BY p.orden, p.id'
        );
        $q->execute(['id' => $ejercicioId]);
        $preguntas = $q->fetchAll();
    }
}

$pageTitle = 'Preguntas';
$activeMenu = 'preguntas';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Preguntas</h2>
        <p>Añade preguntas a cada ejercicio y define sus puntos.</p>
    </div>
    <?php if ($ejercicioId > 0): ?>
        <a class="btn btn-primary" href="<?= e(url('admin/pregunta_form.php?ejercicio_id=' . $ejercicioId)) ?>">Nueva pregunta</a>
    <?php endif; ?>
</section>

<section class="panel">
    <form method="get" class="filter-bar">
        <label for="ejercicio_id">Ejercicio</label>
        <select id="ejercicio_id" name="ejercicio_id" onchange="this.form.submit()">
            <?php if (!$ejercicios): ?>
                <option value="">No hay ejercicios</option>
            <?php else: ?>
                <?php foreach ($ejercicios as $ej): ?>
                    <option value="<?= (int)$ej['id'] ?>" <?= $ejercicioId === (int)$ej['id'] ? 'selected' : '' ?>>
                        <?= e($ej['titulo']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </form>

    <?php if (!$ejercicioActual): ?>
        <p class="empty-cell">Crea primero un ejercicio para poder añadir preguntas.</p>
    <?php else: ?>
        <p class="muted">Ejercicio: <strong><?= e($ejercicioActual['titulo']) ?></strong> · Puntuación máx.: <?= e(format_score((float)$ejercicioActual['puntuacion_maxima'])) ?></p>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Enunciado</th>
                        <th>Tipo</th>
                        <th>Puntos</th>
                        <th>Opciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$preguntas): ?>
                    <tr><td colspan="6" class="empty-cell">Este ejercicio aún no tiene preguntas.</td></tr>
                <?php else: ?>
                    <?php foreach ($preguntas as $p): ?>
                    <tr>
                        <td><?= (int)$p['orden'] ?></td>
                        <td><?= e(mb_strimwidth($p['enunciado'], 0, 100, '…')) ?></td>
                        <td><?= e(label_pregunta_tipo($p['tipo'])) ?></td>
                        <td><?= e(format_score((float)$p['puntos'])) ?></td>
                        <td><?= (int)$p['total_opciones'] ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="<?= e(url('admin/pregunta_form.php?id=' . (int)$p['id'])) ?>">Editar</a>
                            <form method="post" action="<?= e(url('admin/pregunta_accion.php')) ?>" class="inline-form" onsubmit="return confirm('¿Eliminar esta pregunta?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="ejercicio_id" value="<?= $ejercicioId ?>">
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
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
