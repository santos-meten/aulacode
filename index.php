<?php
/**
 * aulacode/index.php
 * Punto de entrada: redirige según sesión y rol.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/init.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

if (($_SESSION['rol'] ?? '') === ROLE_ADMIN) {
    redirect('admin/dashboard.php');
}

redirect('alumno/dashboard.php');
