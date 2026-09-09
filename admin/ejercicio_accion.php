<?php
/**
 * aulacode/admin/ejercicio_accion.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('error', 'Acción no válida.');
    redirect('admin/ejercicios.php');
}

$id = (int) ($_POST['id'] ?? 0);
$accion = (string) ($_POST['accion'] ?? '');

$stmt = db()->prepare('SELECT id, estado FROM ejercicios WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$ej = $stmt->fetch();

if (!$ej) {
    flash_set('error', 'Ejercicio no encontrado.');
    redirect('admin/ejercicios.php');
}

if ($accion === 'publicar') {
    $nuevo = $ej['estado'] === 'PUBLICADO' ? 'BORRADOR' : 'PUBLICADO';
    db()->prepare('UPDATE ejercicios SET estado = :e WHERE id = :id')->execute(['e' => $nuevo, 'id' => $id]);
    flash_set('success', $nuevo === 'PUBLICADO' ? 'Ejercicio publicado.' : 'Ejercicio pasado a borrador.');
} elseif ($accion === 'eliminar') {
    db()->prepare('DELETE FROM ejercicios WHERE id = :id')->execute(['id' => $id]);
    flash_set('success', 'Ejercicio eliminado.');
} else {
    flash_set('error', 'Acción desconocida.');
}

redirect('admin/ejercicios.php');
