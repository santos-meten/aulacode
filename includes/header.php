<?php
/**
 * AulaCode - Cabecera HTML común
 * Variables opcionales: $pageTitle, $bodyClass, $activeMenu
 */

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(APP_TAGLINE) ?>">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($user !== null): ?>
<div class="app-shell">
    <?php
    if ($user['rol'] === ROLE_ADMIN) {
        require __DIR__ . '/navbar_admin.php';
    } else {
        require __DIR__ . '/navbar_alumno.php';
    }
    ?>
    <main class="main-content">
        <?php
        $flash = flash_get();
        if ($flash):
        ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
            <?= e($flash['message']) ?>
        </div>
        <?php endif; ?>
<?php else: ?>
<main class="auth-main">
    <?php
    $flash = flash_get();
    if ($flash):
    ?>
    <div class="alert alert-<?= e($flash['type']) ?> auth-alert" role="alert">
        <?= e($flash['message']) ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
