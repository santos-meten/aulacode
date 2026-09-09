<?php
/**
 * aulacode/auth/logout.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';

logout_user();

// Nueva sesión limpia para el mensaje flash
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

flash_set('success', 'Sesión cerrada correctamente.');
redirect('auth/login.php');
