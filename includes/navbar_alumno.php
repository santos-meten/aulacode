<?php
/**
 * AulaCode - Menú lateral del alumno
 */
$activeMenu = $activeMenu ?? 'dashboard';
$fullName = current_user_fullname();
?>
<aside class="sidebar sidebar-alumno" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-mark">AC</span>
        <div>
            <strong><?= e(APP_NAME) ?></strong>
            <small>Panel alumno</small>
        </div>
        <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">×</button>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= e(url('alumno/dashboard.php')) ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= e(url('alumno/ejercicios.php')) ?>" class="<?= $activeMenu === 'ejercicios' ? 'active' : '' ?>">Mis ejercicios</a>
        <a href="<?= e(url('alumno/resultados.php')) ?>" class="<?= $activeMenu === 'resultados' ? 'active' : '' ?>">Mis resultados</a>
        <a href="<?= e(url('alumno/progreso.php')) ?>" class="<?= $activeMenu === 'progreso' ? 'active' : '' ?>">Mi progreso</a>
        <a href="<?= e(url('alumno/materiales.php')) ?>" class="<?= $activeMenu === 'materiales' ? 'active' : '' ?>">Material de estudio</a>
        <a href="<?= e(url('auth/logout.php')) ?>" class="nav-logout">Cerrar sesión</a>
    </nav>

    <div class="sidebar-user">
        <span class="avatar"><?= e(mb_substr($fullName, 0, 1)) ?></span>
        <div>
            <strong><?= e($fullName) ?></strong>
            <small>Alumno</small>
        </div>
    </div>
</aside>

<header class="topbar">
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú">☰</button>
    <div class="topbar-title">
        <h1><?= e($pageTitle) ?></h1>
    </div>
</header>
