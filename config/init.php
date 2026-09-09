<?php
/**
 * AulaCode - Bootstrap de la aplicación
 */

declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => BASE_PATH . '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Zona horaria del aula (ajustable)
date_default_timezone_set('Europe/Madrid');
