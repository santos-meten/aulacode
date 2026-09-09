<?php
/**
 * AulaCode - Constantes de aplicación
 */

define('APP_NAME', 'AulaCode');
define('APP_TAGLINE', 'Plataforma de Aprendizaje, Práctica y Evaluación');
define('APP_VERSION', '1.0.0');

// Ruta base relativa al DocumentRoot de Apache (carpeta htdocs)
define('BASE_PATH', '/aulacode');

define('ROLE_ADMIN', 'ADMIN');
define('ROLE_ALUMNO', 'ALUMNO');

define('SESSION_NAME', 'aulacode_session');

// Tiempo de vida de sesión (8 horas de clase)
define('SESSION_LIFETIME', 60 * 60 * 8);
