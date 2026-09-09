<?php
/**
 * aulacode/admin/ejercicios.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$ejercicios = db()->query(
    "SELECT e.*,
            (SELECT COUNT(*) FROM preguntas p WHERE p.ejercicio_id = e.id) AS total_preguntas,
            (SELECT COUNT(*) FROM intentos i WHERE i.ejercicio_id = e.id) AS total_intentos
     FROM ejercicios e
     ORDER BY e.creado_en DESC"
)->fetchAll();

$pageTitle = 'Ejercicios';
$activeMenu = 'ejercicios';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Ejercicios</h2>
        <p>Crea, publica y gestiona las actividades del aula.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('admin/ejercicio_form.php')) ?>">Nuevo ejercicio</a>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Duración</th>
                    <th>Puntos</th>
                    <th>Preguntas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$ejercicios): ?>
                <tr><td colspan="7" class="empty-cell">Todavía no hay ejercicios. Crea el primero.</td></tr>
            <?php else: ?>
                <?php foreach ($ejercicios as $ej): ?>
                <tr>
                    <td>
                        <strong><?= e($ej['titulo']) ?></strong>
                        <?php if ($ej['descripcion']): ?>
                            <div class="muted small"><?= e(mb_strimwidth($ej['descripcion'], 0, 80, '…')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e(label_ejercicio_tipo($ej['tipo'])) ?></td>
                    <td><?= (int)$ej['duracion_minutos'] ?> min</td>
                    <td><?= e(format_score((float)$ej['puntuacion_maxima'])) ?></td>
                    <td><?= (int)$ej['total_preguntas'] ?></td>
                    <td>
                        <span class="badge badge-<?= e(strtolower($ej['estado'])) ?>">
                            <?= e(label_ejercicio_estado($ej['estado'])) ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a class="btn btn-small" href="<?= e(url('admin/preguntas.php?ejercicio_id=' . (int)$ej['id'])) ?>">Preguntas</a>
                        <a class="btn btn-small" href="<?= e(url('admin/ejercicio_form.php?id=' . (int)$ej['id'])) ?>">Editar</a>
                        <form method="post" action="<?= e(url('admin/ejercicio_accion.php')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$ej['id'] ?>">
                            <input type="hidden" name="accion" value="publicar">
                            <button type="submit" class="btn btn-small btn-secondary">
                                <?= $ej['estado'] === 'PUBLICADO' ? 'Despublicar' : 'Publicar' ?>
                            </button>
                        </form>
                        <form method="post" action="<?= e(url('admin/ejercicio_accion.php')) ?>" class="inline-form" onsubmit="return confirm('¿Eliminar este ejercicio y sus preguntas?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$ej['id'] ?>">
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
