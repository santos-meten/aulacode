<?php
/**
 * aulacode/alumno/resultados.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_alumno();

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT i.started_at, i.finished_at, i.estado, i.puntuacion,
            e.titulo, e.puntuacion_maxima, r.porcentaje
     FROM intentos i
     INNER JOIN ejercicios e ON e.id = i.ejercicio_id
     LEFT JOIN resultados r ON r.intento_id = i.id
     WHERE i.usuario_id = :id
     ORDER BY i.started_at DESC"
);
$stmt->execute(['id' => $user['id']]);
$filas = $stmt->fetchAll();

$pageTitle = 'Mis resultados';
$activeMenu = 'resultados';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Mis resultados</h2>
        <p>Historial de intentos y puntuaciones.</p>
    </div>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ejercicio</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$filas): ?>
                <tr><td colspan="5" class="empty-cell">Aún no tienes resultados. Cuando finalices ejercicios aparecerán aquí.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?= e($f['titulo']) ?></td>
                    <td><?= e($f['started_at'] ? date('d/m/Y H:i', strtotime($f['started_at'])) : '—') ?></td>
                    <td><?= e($f['finished_at'] ? date('d/m/Y H:i', strtotime($f['finished_at'])) : '—') ?></td>
                    <td><?= e(label_intento_estado($f['estado'])) ?></td>
                    <td>
                        <?php if ($f['puntuacion'] !== null): ?>
                            <?= e(format_score((float)$f['puntuacion'])) ?> / <?= e(format_score((float)$f['puntuacion_maxima'])) ?>
                            <?php if ($f['porcentaje'] !== null): ?>
                                (<?= e(format_percent((float)$f['porcentaje'])) ?>)
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
