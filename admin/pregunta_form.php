<?php
/**
 * aulacode/admin/pregunta_form.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';
require_admin();

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ejercicioId = isset($_GET['ejercicio_id']) ? (int) $_GET['ejercicio_id'] : 0;

$pregunta = [
    'ejercicio_id' => $ejercicioId,
    'enunciado' => '',
    'tipo' => 'OPCION_MULTIPLE',
    'puntos' => 1,
    'respuesta_esperada' => '',
    'orden' => 1,
];
$opciones = [
    ['texto' => '', 'es_correcta' => 1],
    ['texto' => '', 'es_correcta' => 0],
    ['texto' => '', 'es_correcta' => 0],
    ['texto' => '', 'es_correcta' => 0],
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM preguntas WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Pregunta no encontrada.');
        redirect('admin/preguntas.php');
    }
    $pregunta = $row;
    $ejercicioId = (int) $row['ejercicio_id'];

    $ops = $pdo->prepare('SELECT texto, es_correcta FROM opciones WHERE pregunta_id = :id ORDER BY orden, id');
    $ops->execute(['id' => $id]);
    $loaded = $ops->fetchAll();
    if ($loaded) {
        $opciones = $loaded;
        while (count($opciones) < 4) {
            $opciones[] = ['texto' => '', 'es_correcta' => 0];
        }
    }
}

if ($ejercicioId <= 0) {
    flash_set('error', 'Debes indicar un ejercicio.');
    redirect('admin/preguntas.php');
}

$checkEj = $pdo->prepare('SELECT id, titulo FROM ejercicios WHERE id = :id');
$checkEj->execute(['id' => $ejercicioId]);
$ejercicio = $checkEj->fetch();
if (!$ejercicio) {
    flash_set('error', 'Ejercicio no encontrado.');
    redirect('admin/preguntas.php');
}

$tipos = ['OPCION_MULTIPLE', 'VERDADERO_FALSO', 'RESPUESTA_CORTA', 'SQL', 'PHP', 'TEXTO_LARGO'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errores[] = 'Token de seguridad no válido.';
    } else {
        $pregunta['enunciado'] = trim((string) ($_POST['enunciado'] ?? ''));
        $pregunta['tipo'] = (string) ($_POST['tipo'] ?? 'OPCION_MULTIPLE');
        $pregunta['puntos'] = max(0.1, (float) ($_POST['puntos'] ?? 1));
        $pregunta['respuesta_esperada'] = trim((string) ($_POST['respuesta_esperada'] ?? ''));
        $pregunta['orden'] = max(1, (int) ($_POST['orden'] ?? 1));

        $textos = $_POST['opcion_texto'] ?? [];
        $correcta = (int) ($_POST['opcion_correcta'] ?? 0);
        $opciones = [];
        foreach ((array) $textos as $i => $texto) {
            $texto = trim((string) $texto);
            if ($texto === '') {
                continue;
            }
            $opciones[] = [
                'texto' => $texto,
                'es_correcta' => ($i === $correcta) ? 1 : 0,
            ];
        }

        if ($pregunta['enunciado'] === '') {
            $errores[] = 'El enunciado es obligatorio.';
        }
        if (!in_array($pregunta['tipo'], $tipos, true)) {
            $errores[] = 'Tipo de pregunta no válido.';
        }

        if (in_array($pregunta['tipo'], ['OPCION_MULTIPLE', 'VERDADERO_FALSO'], true)) {
            if (count($opciones) < 2) {
                $errores[] = 'Debes indicar al menos 2 opciones.';
            }
            $hayCorrecta = false;
            foreach ($opciones as $op) {
                if ((int)$op['es_correcta'] === 1) {
                    $hayCorrecta = true;
                }
            }
            if (!$hayCorrecta) {
                $errores[] = 'Marca la opción correcta.';
            }
        }

        if ($pregunta['tipo'] === 'VERDADERO_FALSO' && count($opciones) === 0) {
            $opciones = [
                ['texto' => 'Verdadero', 'es_correcta' => $correcta === 0 ? 1 : 0],
                ['texto' => 'Falso', 'es_correcta' => $correcta === 1 ? 1 : 0],
            ];
            $errores = array_values(array_filter($errores, static fn($e) => !str_contains($e, '2 opciones')));
        }

        if ($pregunta['tipo'] === 'RESPUESTA_CORTA' && $pregunta['respuesta_esperada'] === '') {
            $errores[] = 'Indica la respuesta esperada para corrección automática.';
        }

        if (!$errores) {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE preguntas SET enunciado=:enunciado, tipo=:tipo, puntos=:puntos,
                     respuesta_esperada=:resp, orden=:orden WHERE id=:id'
                );
                $stmt->execute([
                    'enunciado' => $pregunta['enunciado'],
                    'tipo' => $pregunta['tipo'],
                    'puntos' => $pregunta['puntos'],
                    'resp' => $pregunta['respuesta_esperada'] !== '' ? $pregunta['respuesta_esperada'] : null,
                    'orden' => $pregunta['orden'],
                    'id' => $id,
                ]);
                $preguntaId = $id;
                $pdo->prepare('DELETE FROM opciones WHERE pregunta_id = :id')->execute(['id' => $preguntaId]);
                flash_set('success', 'Pregunta actualizada.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO preguntas (ejercicio_id, enunciado, tipo, puntos, respuesta_esperada, orden)
                     VALUES (:ej, :enunciado, :tipo, :puntos, :resp, :orden)'
                );
                $stmt->execute([
                    'ej' => $ejercicioId,
                    'enunciado' => $pregunta['enunciado'],
                    'tipo' => $pregunta['tipo'],
                    'puntos' => $pregunta['puntos'],
                    'resp' => $pregunta['respuesta_esperada'] !== '' ? $pregunta['respuesta_esperada'] : null,
                    'orden' => $pregunta['orden'],
                ]);
                $preguntaId = (int) $pdo->lastInsertId();
                flash_set('success', 'Pregunta creada.');
            }

            if (in_array($pregunta['tipo'], ['OPCION_MULTIPLE', 'VERDADERO_FALSO'], true)) {
                $ins = $pdo->prepare(
                    'INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES (:p, :t, :c, :o)'
                );
                foreach ($opciones as $i => $op) {
                    $ins->execute([
                        'p' => $preguntaId,
                        't' => $op['texto'],
                        'c' => (int) $op['es_correcta'],
                        'o' => $i + 1,
                    ]);
                }
            }

            redirect('admin/preguntas.php?ejercicio_id=' . $ejercicioId);
        }
    }
}

// Prefill V/F
if ($pregunta['tipo'] === 'VERDADERO_FALSO' && trim($opciones[0]['texto'] ?? '') === '') {
    $opciones = [
        ['texto' => 'Verdadero', 'es_correcta' => 1],
        ['texto' => 'Falso', 'es_correcta' => 0],
        ['texto' => '', 'es_correcta' => 0],
        ['texto' => '', 'es_correcta' => 0],
    ];
}

$pageTitle = $id > 0 ? 'Editar pregunta' : 'Nueva pregunta';
$activeMenu = 'preguntas';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="toolbar">
    <div>
        <h2><?= e($pageTitle) ?></h2>
        <p>Ejercicio: <strong><?= e($ejercicio['titulo']) ?></strong></p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('admin/preguntas.php?ejercicio_id=' . $ejercicioId)) ?>">Volver</a>
</section>

<section class="panel form-panel">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid" id="preguntaForm">
        <?= csrf_field() ?>

        <label for="enunciado">Enunciado</label>
        <textarea id="enunciado" name="enunciado" rows="3" required><?= e($pregunta['enunciado']) ?></textarea>

        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <?php foreach ($tipos as $t): ?>
                <option value="<?= e($t) ?>" <?= $pregunta['tipo'] === $t ? 'selected' : '' ?>><?= e(label_pregunta_tipo($t)) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="puntos">Puntos</label>
        <input type="number" step="0.01" min="0.1" id="puntos" name="puntos" value="<?= e((string)$pregunta['puntos']) ?>">

        <label for="orden">Orden</label>
        <input type="number" min="1" id="orden" name="orden" value="<?= (int)$pregunta['orden'] ?>">

        <div id="bloqueOpciones">
            <h3>Opciones</h3>
            <p class="muted small">Marca la respuesta correcta.</p>
            <?php foreach ($opciones as $i => $op): ?>
                <div class="option-row">
                    <input type="radio" name="opcion_correcta" value="<?= $i ?>" <?= (int)$op['es_correcta'] === 1 ? 'checked' : '' ?> aria-label="Correcta">
                    <input type="text" name="opcion_texto[]" maxlength="500" value="<?= e($op['texto']) ?>" placeholder="Opción <?= $i + 1 ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div id="bloqueCorta">
            <label for="respuesta_esperada">Respuesta esperada (respuesta corta / SQL)</label>
            <input type="text" id="respuesta_esperada" name="respuesta_esperada" value="<?= e($pregunta['respuesta_esperada'] ?? '') ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar pregunta</button>
        </div>
    </form>
</section>

<script>
(function () {
    var tipo = document.getElementById('tipo');
    var ops = document.getElementById('bloqueOpciones');
    var corta = document.getElementById('bloqueCorta');
    function sync() {
        var t = tipo.value;
        var showOps = (t === 'OPCION_MULTIPLE' || t === 'VERDADERO_FALSO');
        var showCorta = (t === 'RESPUESTA_CORTA' || t === 'SQL');
        ops.style.display = showOps ? 'block' : 'none';
        corta.style.display = showCorta ? 'block' : 'none';
    }
    tipo.addEventListener('change', sync);
    sync();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
