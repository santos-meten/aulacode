<?php
/**
 * aulacode/admin/alumno_accion.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('error', 'Acción no válida.');
    redirect('admin/alumnos.php');
}

$id = (int) ($_POST['id'] ?? 0);
$accion = (string) ($_POST['accion'] ?? '');

$stmt = db()->prepare("SELECT id, estado FROM usuarios WHERE id = :id AND rol = 'ALUMNO' LIMIT 1");
$stmt->execute(['id' => $id]);
$alumno = $stmt->fetch();

if (!$alumno) {
    flash_set('error', 'Alumno no encontrado.');
    redirect('admin/alumnos.php');
}

if ($accion === 'toggle') {
    $nuevo = (int) $alumno['estado'] === 1 ? 0 : 1;
    $upd = db()->prepare('UPDATE usuarios SET estado = :estado WHERE id = :id');
    $upd->execute(['estado' => $nuevo, 'id' => $id]);
    flash_set('success', $nuevo === 1 ? 'Alumno activado.' : 'Alumno desactivado.');
} elseif ($accion === 'eliminar') {
    $del = db()->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'ALUMNO'");
    $del->execute(['id' => $id]);
    flash_set('success', 'Alumno eliminado.');
} else {
    flash_set('error', 'Acción desconocida.');
}

redirect('admin/alumnos.php');
