<?php
/**
 * aulacode/admin/ejercicio_form.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ej = [
    'titulo' => '',
    'descripcion' => '',
    'tipo' => 'CUESTIONARIO',
    'duracion_minutos' => 20,
    'fecha_inicio' => '',
    'fecha_fin' => '',
    'puntuacion_maxima' => 10,
    'estado' => 'BORRADOR',
    'grupo_id' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM ejercicios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Ejercicio no encontrado.');
        redirect('admin/ejercicios.php');
    }
    $ej = $row;
    $ej['fecha_inicio'] = $row['fecha_inicio'] ? date('Y-m-d\TH:i', strtotime($row['fecha_inicio'])) : '';
    $ej['fecha_fin'] = $row['fecha_fin'] ? date('Y-m-d\TH:i', strtotime($row['fecha_fin'])) : '';

    $asig = $pdo->prepare('SELECT grupo_id FROM asignaciones WHERE ejercicio_id = :id AND grupo_id IS NOT NULL LIMIT 1');
    $asig->execute(['id' => $id]);
    $ej['grupo_id'] = $asig->fetchColumn() ?: '';
}

$tipos = ['CUESTIONARIO', 'SQL', 'PHP', 'RESPUESTA_ESCRITA', 'PROYECTO'];
$estados = ['BORRADOR', 'PUBLICADO', 'CERRADO'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errores[] = 'Token de seguridad no válido.';
    } else {
        $ej['titulo'] = trim((string) ($_POST['titulo'] ?? ''));
        $ej['descripcion'] = trim((string) ($_POST['descripcion'] ?? ''));
        $ej['tipo'] = (string) ($_POST['tipo'] ?? 'CUESTIONARIO');
        $ej['duracion_minutos'] = max(1, (int) ($_POST['duracion_minutos'] ?? 20));
        $ej['puntuacion_maxima'] = max(0.1, (float) ($_POST['puntuacion_maxima'] ?? 10));
        $ej['estado'] = (string) ($_POST['estado'] ?? 'BORRADOR');
        $ej['grupo_id'] = $_POST['grupo_id'] !== '' ? (int) $_POST['grupo_id'] : null;
        $fi = trim((string) ($_POST['fecha_inicio'] ?? ''));
        $ff = trim((string) ($_POST['fecha_fin'] ?? ''));
        $ej['fecha_inicio'] = $fi;
        $ej['fecha_fin'] = $ff;

        if ($ej['titulo'] === '') {
            $errores[] = 'El título es obligatorio.';
        }
        if (!in_array($ej['tipo'], $tipos, true)) {
            $errores[] = 'Tipo de ejercicio no válido.';
        }
        if (!in_array($ej['estado'], $estados, true)) {
            $errores[] = 'Estado no válido.';
        }

        $fechaInicioSql = $fi !== '' ? date('Y-m-d H:i:s', strtotime($fi)) : null;
        $fechaFinSql = $ff !== '' ? date('Y-m-d H:i:s', strtotime($ff)) : null;

        if (!$errores) {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE ejercicios SET titulo=:titulo, descripcion=:descripcion, tipo=:tipo,
                     duracion_minutos=:duracion, fecha_inicio=:fi, fecha_fin=:ff,
                     puntuacion_maxima=:puntos, estado=:estado
                     WHERE id=:id'
                );
                $stmt->execute([
                    'titulo' => $ej['titulo'],
                    'descripcion' => $ej['descripcion'] !== '' ? $ej['descripcion'] : null,
                    'tipo' => $ej['tipo'],
                    'duracion' => $ej['duracion_minutos'],
                    'fi' => $fechaInicioSql,
                    'ff' => $fechaFinSql,
                    'puntos' => $ej['puntuacion_maxima'],
                    'estado' => $ej['estado'],
                    'id' => $id,
                ]);
                $ejercicioId = $id;
                flash_set('success', 'Ejercicio actualizado.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO ejercicios
                     (titulo, descripcion, tipo, duracion_minutos, fecha_inicio, fecha_fin, puntuacion_maxima, estado, creado_por)
                     VALUES (:titulo, :descripcion, :tipo, :duracion, :fi, :ff, :puntos, :estado, :creado_por)'
                );
                $stmt->execute([
                    'titulo' => $ej['titulo'],
                    'descripcion' => $ej['descripcion'] !== '' ? $ej['descripcion'] : null,
                    'tipo' => $ej['tipo'],
                    'duracion' => $ej['duracion_minutos'],
                    'fi' => $fechaInicioSql,
                    'ff' => $fechaFinSql,
                    'puntos' => $ej['puntuacion_maxima'],
                    'estado' => $ej['estado'],
                    'creado_por' => (int) current_user()['id'],
                ]);
                $ejercicioId = (int) $pdo->lastInsertId();
                flash_set('success', 'Ejercicio creado.');
            }

            // Asignación por grupo (reemplaza asignaciones de grupo previas)
            $pdo->prepare('DELETE FROM asignaciones WHERE ejercicio_id = :id AND grupo_id IS NOT NULL')
                ->execute(['id' => $ejercicioId]);
            if (!empty($ej['grupo_id'])) {
                $pdo->prepare(
                    'INSERT INTO asignaciones (ejercicio_id, grupo_id) VALUES (:e, :g)'
                )->execute(['e' => $ejercicioId, 'g' => $ej['grupo_id']]);
            }

            redirect('admin/ejercicios.php');
        }
    }
}

$grupos = grupos_activos();
$pageTitle = $id > 0 ? 'Editar ejercicio' : 'Nuevo ejercicio';
$activeMenu = 'ejercicios';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2><?= e($pageTitle) ?></h2>
        <p>Define duración, puntuación y publicación.</p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('admin/ejercicios.php')) ?>">Volver</a>
</section>

<section class="panel form-panel">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>

        <label for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" required maxlength="200" value="<?= e($ej['titulo']) ?>">

        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="4"><?= e($ej['descripcion'] ?? '') ?></textarea>

        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <?php foreach ($tipos as $t): ?>
                <option value="<?= e($t) ?>" <?= $ej['tipo'] === $t ? 'selected' : '' ?>><?= e(label_ejercicio_tipo($t)) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="duracion_minutos">Duración (minutos)</label>
        <input type="number" id="duracion_minutos" name="duracion_minutos" min="1" max="600" value="<?= (int)$ej['duracion_minutos'] ?>">

        <label for="fecha_inicio">Fecha de inicio</label>
        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" value="<?= e($ej['fecha_inicio'] ?? '') ?>">

        <label for="fecha_fin">Fecha de finalización</label>
        <input type="datetime-local" id="fecha_fin" name="fecha_fin" value="<?= e($ej['fecha_fin'] ?? '') ?>">

        <label for="puntuacion_maxima">Puntuación máxima</label>
        <input type="number" step="0.01" id="puntuacion_maxima" name="puntuacion_maxima" min="0.1" value="<?= e((string)$ej['puntuacion_maxima']) ?>">

        <label for="estado">Estado</label>
        <select id="estado" name="estado">
            <?php foreach ($estados as $est): ?>
                <option value="<?= e($est) ?>" <?= $ej['estado'] === $est ? 'selected' : '' ?>><?= e(label_ejercicio_estado($est)) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="grupo_id">Asignar a grupo (opcional)</label>
        <select id="grupo_id" name="grupo_id">
            <option value="">Todos los alumnos (sin restricción)</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= (string)($ej['grupo_id'] ?? '') === (string)$g['id'] ? 'selected' : '' ?>>
                    <?= e($g['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar ejercicio</button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
