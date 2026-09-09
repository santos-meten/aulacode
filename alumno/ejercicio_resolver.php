<?php
/**
 * aulacode/alumno/ejercicio_resolver.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_alumno();

$user = current_user();
$pdo = db();

$ejercicioId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$ejercicioId) {
    flash('error', 'Ejercicio no válido.');
    redirect('ejercicios.php');
}

// 1. Obtener los datos del ejercicio validando permisos y asignación
$stmt = $pdo->prepare("
    SELECT e.*
    FROM ejercicios e
    WHERE e.id = :id
      AND e.estado = 'PUBLICADO'
      AND (
        NOT EXISTS (SELECT 1 FROM asignaciones a WHERE a.ejercicio_id = e.id)
        OR EXISTS (
          SELECT 1 FROM asignaciones a
          WHERE a.ejercicio_id = e.id
            AND (a.usuario_id = :uid OR (a.grupo_id IS NOT NULL AND a.grupo_id = :gid))
        )
      )
    LIMIT 1
");
$stmt->execute([
    'id'  => $ejercicioId,
    'uid' => $user['id'],
    'gid' => $user['grupo_id'] ?? null,
]);
$ejercicio = $stmt->fetch();

if (!$ejercicio) {
    flash('error', 'El ejercicio no está disponible o no tienes permisos para acceder a él.');
    redirect('ejercicios.php');
}

// 2. Comprobar si ya existe un intento en proceso o finalizado
$stmtIntento = $pdo->prepare("
    SELECT * FROM intentos
    WHERE ejercicio_id = :ej_id AND usuario_id = :uid
    ORDER BY id DESC
    LIMIT 1
");
$stmtIntento->execute([
    'ej_id' => $ejercicio['id'],
    'uid'   => $user['id'],
]);
$intento = $stmtIntento->fetch();

// Si el último intento ya está finalizado/corregido y no se permiten más, avisar
if ($intento && in_array($intento['estado'], ['FINALIZADO', 'CORREGIDO', 'ENTREGADO'], true)) {
    flash('info', 'Ya has completado este ejercicio.');
    redirect('ejercicios.php');
}

// Si no existe intento o está vacío, creamos un nuevo intento
if (!$intento) {
    $stmtNuevo = $pdo->prepare("
        INSERT INTO intentos (ejercicio_id, usuario_id, fecha_inicio, estado, codigo_respuesta)
        VALUES (:ej_id, :uid, NOW(), 'EN_PROCESO', :plantilla)
    ");
    $stmtNuevo->execute([
        'ej_id'     => $ejercicio['id'],
        'uid'       => $user['id'],
        'plantilla' => $ejercicio['codigo_inicial'] ?? $ejercicio['plantilla'] ?? '',
    ]);
    
    // Recargamos los datos del intento recién creado
    $stmtIntento->execute(['ej_id' => $ejercicio['id'], 'uid' => $user['id']]);
    $intento = $stmtIntento->fetch();
}

// 3. Procesar el envío de la solución (POST)
$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoRespuesta = trim($_POST['codigo_respuesta'] ?? '');
    $accion = $_POST['accion'] ?? 'guardar'; // 'guardar' o 'finalizar'

    if ($accion === 'finalizar') {
        $stmtUpdate = $pdo->prepare("
            UPDATE intentos
            SET codigo_respuesta = :codigo,
                fecha_fin = NOW(),
                estado = 'FINALIZADO'
            WHERE id = :intento_id AND usuario_id = :uid
        ");
        $stmtUpdate->execute([
            'codigo'     => $codigoRespuesta,
            'intento_id' => $intento['id'],
            'uid'        => $user['id'],
        ]);

        flash('success', '¡Ejercicio entregado correctamente!');
        redirect('ejercicios.php');
    } else {
        // Solo autoguardado / borrador
        $stmtUpdate = $pdo->prepare("
            UPDATE intentos
            SET codigo_respuesta = :codigo
            WHERE id = :intento_id AND usuario_id = :uid
        ");
        $stmtUpdate->execute([
            'codigo'     => $codigoRespuesta,
            'intento_id' => $intento['id'],
            'uid'        => $user['id'],
        ]);
        $intento['codigo_respuesta'] = $codigoRespuesta;
        $mensajeExito = 'Borrador guardado.';
    }
}

$pageTitle = 'Resolviendo: ' . ($ejercicio['titulo'] ?? 'Ejercicio');
$activeMenu = 'ejercicios';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2><?= e($ejercicio['titulo']) ?></h2>
        <p class="muted">
            Tipo: <strong><?= e(label_ejercicio_tipo($ejercicio['tipo'] ?? '')) ?></strong> | 
            Puntaje máximo: <strong><?= e(format_score((float)$ejercicio['puntuacion_maxima'])) ?> pts</strong>
            <?php if (!empty($ejercicio['duracion_minutos'])): ?>
                | Tiempo estimado: <strong><?= (int)$ejercicio['duracion_minutos'] ?> min</strong>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="ejercicios.php" class="btn btn-outline">&larr; Volver a la lista</a>
    </div>
</section>

<?php if ($mensajeExito): ?>
    <div class="alert alert-success"><?= e($mensajeExito) ?></div>
<?php endif; ?>
<?php if ($mensajeError): ?>
    <div class="alert alert-danger"><?= e($mensajeError) ?></div>
<?php endif; ?>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 20px; margin-top: 15px;">
    <!-- Columna Izquierda: Enunciado -->
    <section class="panel">
        <h3>Instrucciones</h3>
        <hr>
        <div class="ejercicio-descripcion" style="line-height: 1.6; margin-top: 15px;">
            <?= nl2br(e($ejercicio['descripcion'] ?? 'Sin descripción detallada.')) ?>
        </div>
    </section>

    <!-- Columna Derecha: Editor / Respuesta -->
    <section class="panel">
        <h3>Tu Solución</h3>
        <hr>
        <form method="POST" style="margin-top: 15px;">
            <div class="form-group">
                <label for="codigo_respuesta">Escribe tu código / respuesta:</label>
                <textarea 
                    name="codigo_respuesta" 
                    id="codigo_respuesta" 
                    rows="16" 
                    class="form-control" 
                    style="font-family: monospace; width: 100%; padding: 10px; font-size: 14px; background: #1e1e1e; color: #d4d4d4; border-radius: 4px;"
                    placeholder="// Escribe aquí tu código o solución..."
                ><?= e($intento['codigo_respuesta'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
                <button type="submit" name="accion" value="guardar" class="btn btn-secondary">
                    Guardar borrador
                </button>
                <button type="submit" name="accion" value="finalizar" class="btn btn-primary" onclick="return confirm('¿Estás seguro de que deseas entregar este ejercicio? Ya no podrás editarlo.');">
                    Entregar ejercicio
                </button>
            </div>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>