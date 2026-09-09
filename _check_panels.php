<?php
declare(strict_types=1);
require_once __DIR__ . '/config/init.php';

$pages = [
    'admin/alumnos.php',
    'admin/ejercicios.php',
    'admin/preguntas.php',
    'admin/resultados.php',
    'admin/estadisticas.php',
    'admin/configuracion.php',
    'alumno/ejercicios.php',
    'alumno/resultados.php',
    'alumno/progreso.php',
    'alumno/materiales.php',
];

foreach ($pages as $p) {
    $path = __DIR__ . '/' . $p;
    echo $p . ': ' . (is_file($path) ? 'OK' : 'MISSING') . PHP_EOL;
}

$c = [
    'ejercicios' => (int)db()->query('SELECT COUNT(*) FROM ejercicios')->fetchColumn(),
    'preguntas' => (int)db()->query('SELECT COUNT(*) FROM preguntas')->fetchColumn(),
    'materiales' => (int)db()->query('SELECT COUNT(*) FROM materiales WHERE publicado=1')->fetchColumn(),
    'alumnos' => (int)db()->query("SELECT COUNT(*) FROM usuarios WHERE rol='ALUMNO'")->fetchColumn(),
];
foreach ($c as $k => $v) {
    echo "$k=$v\n";
}

$_SESSION = [];
echo 'login_admin=' . (attempt_login('admin', 'admin123') ? 'OK' : 'FAIL') . PHP_EOL;
logout_user();
if (session_status() === PHP_SESSION_NONE) { session_name(SESSION_NAME); session_start(); }
echo 'login_alumno=' . (attempt_login('alumno1', 'alumno123') ? 'OK' : 'FAIL') . PHP_EOL;
