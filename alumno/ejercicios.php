<?php
/**
 * aulacode/alumno/ejercicios.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_alumno();

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT e.*,
            (SELECT i.estado FROM intentos i
              WHERE i.ejercicio_id = e.id AND i.usuario_id = :uid
              ORDER BY i.id DESC LIMIT 1) AS mi_estado,
            (SELECT i.puntuacion FROM intentos i
              WHERE i.ejercicio_id = e.id AND i.usuario_id = :uid2
              ORDER BY i.id DESC LIMIT 1) AS mi_nota,
            (SELECT i.id FROM intentos i
              WHERE i.ejercicio_id = e.id AND i.usuario_id = :uid3
              ORDER BY i.id DESC LIMIT 1) AS mi_intento_id
     FROM ejercicios e
     WHERE e.estado = 'PUBLICADO'
       AND (
         NOT EXISTS (SELECT 1 FROM asignaciones a WHERE a.ejercicio_id = e.id)
         OR EXISTS (
           SELECT 1 FROM asignaciones a
           WHERE a.ejercicio_id = e.id
             AND (a.usuario_id = :uid4 OR (a.grupo_id IS NOT NULL AND a.grupo_id = :gid))
         )
       )
     ORDER BY e.fecha_inicio IS NULL, e.fecha_inicio, e.titulo"
);
$stmt->execute([
    'uid'  => $user['id'],
    'uid2' => $user['id'],
    'uid3' => $user['id'],
    'uid4' => $user['id'],
    'gid'  => $user['grupo_id'],
]);
$ejercicios = $stmt->fetchAll();

$pageTitle = 'Mis ejercicios';
$activeMenu = 'ejercicios';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2>Mis ejercicios</h2>
        <p>Actividades publicadas para ti.</p>
    </div>
</section>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ejercicio</th>
                    <th>Tipo</th>
                    <th>Duración</th>
                    <th>Puntos</th>
                    <th>Estado</th>
                    <th>Nota</th>
                    <th style="text-align: right;">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$ejercicios): ?>
                <tr><td colspan="7" class="empty-cell">No hay ejercicios publicados para ti todavía.</td></tr>
            <?php else: ?>
                <?php foreach ($ejercicios as $ej): ?>
                <?php
                    $ahora = date('Y-m-d H:i:s');
                    $aunNoInicia = !empty($ej['fecha_inicio']) && $ej['fecha_inicio'] > $ahora;
                    $haFinalizado = !empty($ej['fecha_fin']) && $ej['fecha_fin'] < $ahora;
                ?>
                <tr>
                    <td>
                        <strong><?= e($ej['titulo']) ?></strong>
                        <?php if ($ej['descripcion']): ?>
                            <div class="muted small"><?= e(mb_strimwidth($ej['descripcion'], 0, 90, '…')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e(label_ejercicio_tipo($ej['tipo'])) ?></td>
                    <td><?= (int)$ej['duracion_minutos'] ?> min</td>
                    <td><?= e(format_score((float)$ej['puntuacion_maxima'])) ?></td>
                    <td>
                        <?php if ($ej['mi_estado']): ?>
                            <span class="badge"><?= e(label_intento_estado($ej['mi_estado'])) ?></span>
                        <?php else: ?>
                            <span class="badge badge-publicado">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $ej['mi_nota'] !== null ? e(format_score((float)$ej['mi_nota'])) : '—' ?>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($aunNoInicia): ?>
                            <button class="btn btn-sm" disabled title="Disponible a partir de <?= e($ej['fecha_inicio']) ?>">No disponible</button>
                        <?php elseif ($ej['mi_estado'] === 'EN_PROCESO'): ?>
                            <a href="ejercicio_resolver.php?id=<?= (int)$ej['id'] ?>" class="btn btn-sm btn-warning">Continuar</a>
                        <?php elseif (in_array($ej['mi_estado'], ['FINALIZADO', 'CORREGIDO', 'ENTREGADO'], true)): ?>
                            <a href="ejercicio_ver.php?id=<?= (int)$ej['id'] ?>" class="btn btn-sm btn-secondary">Ver resultado</a>
                        <?php elseif ($haFinalizado): ?>
                            <span class="badge badge-danger">Expirado</span>
                        <?php else: ?>
                            <a href="ejercicio_resolver.php?id=<?= (int)$ej['id'] ?>" class="btn btn-sm btn-primary">Comenzar</a>
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