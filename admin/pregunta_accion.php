<?php
/**
 * aulacode/admin/pregunta_accion.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('error', 'Acción no válida.');
    redirect('admin/preguntas.php');
}

$id = (int) ($_POST['id'] ?? 0);
$ejercicioId = (int) ($_POST['ejercicio_id'] ?? 0);
$accion = (string) ($_POST['accion'] ?? '');

if ($accion === 'eliminar' && $id > 0) {
    db()->prepare('DELETE FROM preguntas WHERE id = :id')->execute(['id' => $id]);
    flash_set('success', 'Pregunta eliminada.');
}

redirect('admin/preguntas.php?ejercicio_id=' . $ejercicioId);
