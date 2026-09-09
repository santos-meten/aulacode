<?php
/**
 * AulaCode - Funciones auxiliares
 */

declare(strict_types=1);

/**
 * Escapa texto para salida HTML (protección XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Construye una URL interna de la aplicación.
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_PATH . ($path !== '' ? '/' . $path : '');
}

/**
 * Redirige a una ruta interna y termina la ejecución.
 */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Genera o recupera el token CSRF de la sesión.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Campo hidden con el token CSRF.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida el token CSRF recibido por POST.
 */
function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if ($token === '' || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Guarda un mensaje flash en sesión.
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Obtiene y limpia el mensaje flash.
 */
function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Formatea un número como porcentaje.
 */
function format_percent(?float $value): string
{
    if ($value === null) {
        return '—';
    }

    return number_format($value, 1, ',', '.') . '%';
}

/**
 * Formatea una nota numérica.
 */
function format_score(?float $value): string
{
    if ($value === null) {
        return '—';
    }

    return number_format($value, 2, ',', '.');
}

/**
 * Etiqueta legible para estado de ejercicio.
 */
function label_ejercicio_estado(string $estado): string
{
    return match ($estado) {
        'BORRADOR'  => 'Borrador',
        'PUBLICADO' => 'Publicado',
        'CERRADO'   => 'Cerrado',
        default     => $estado,
    };
}

/**
 * Etiqueta legible para tipo de ejercicio.
 */
function label_ejercicio_tipo(string $tipo): string
{
    return match ($tipo) {
        'CUESTIONARIO'      => 'Cuestionario',
        'SQL'               => 'SQL',
        'PHP'               => 'PHP',
        'RESPUESTA_ESCRITA' => 'Respuesta escrita',
        'PROYECTO'          => 'Proyecto',
        default             => $tipo,
    };
}

/**
 * Etiqueta legible para tipo de pregunta.
 */
function label_pregunta_tipo(string $tipo): string
{
    return match ($tipo) {
        'OPCION_MULTIPLE' => 'Opción múltiple',
        'VERDADERO_FALSO' => 'Verdadero / Falso',
        'RESPUESTA_CORTA' => 'Respuesta corta',
        'SQL'             => 'SQL',
        'PHP'             => 'PHP',
        'TEXTO_LARGO'     => 'Texto largo',
        default           => $tipo,
    };
}

/**
 * Etiqueta legible para estado de intento.
 */
function label_intento_estado(string $estado): string
{
    return match ($estado) {
        'PENDIENTE'            => 'Pendiente',
        'EN_CURSO'             => 'En curso',
        'FINALIZADO'           => 'Finalizado',
        'TIEMPO_AGOTADO'       => 'Tiempo agotado',
        'PENDIENTE_CORRECCION' => 'Pendiente corrección',
        default                => $estado,
    };
}

/**
 * Lista de grupos activos.
 */
function grupos_activos(): array
{
    return db()->query(
        'SELECT id, nombre FROM grupos WHERE activo = 1 ORDER BY nombre'
    )->fetchAll();
}

/**
 * Comprueba si un ejercicio está disponible para un alumno.
 */
function ejercicio_disponible_para_alumno(int $ejercicioId, int $usuarioId, ?int $grupoId): bool
{
    $stmt = db()->prepare(
        "SELECT e.id
         FROM ejercicios e
         WHERE e.id = :id AND e.estado = 'PUBLICADO'
           AND (
             NOT EXISTS (SELECT 1 FROM asignaciones a WHERE a.ejercicio_id = e.id)
             OR EXISTS (
               SELECT 1 FROM asignaciones a
               WHERE a.ejercicio_id = e.id
                 AND (a.usuario_id = :uid OR (a.grupo_id IS NOT NULL AND a.grupo_id = :gid))
             )
           )
         LIMIT 1"
    );
    $stmt->execute([
        'id'  => $ejercicioId,
        'uid' => $usuarioId,
        'gid' => $grupoId,
    ]);

    return (bool) $stmt->fetchColumn();
}
