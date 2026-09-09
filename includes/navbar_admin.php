<?php
/**
 * AulaCode - Menú lateral del profesor
 */
$activeMenu = $activeMenu ?? 'dashboard';
$fullName = current_user_fullname();
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-mark">AC</span>
        <div>
            <strong><?= e(APP_NAME) ?></strong>
            <small>Panel profesor</small>
        </div>
        <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">×</button>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= e(url('admin/alumnos.php')) ?>" class="<?= $activeMenu === 'alumnos' ? 'active' : '' ?>">Alumnos</a>
        <a href="<?= e(url('admin/ejercicios.php')) ?>" class="<?= $activeMenu === 'ejercicios' ? 'active' : '' ?>">Ejercicios</a>
        <a href="<?= e(url('admin/preguntas.php')) ?>" class="<?= $activeMenu === 'preguntas' ? 'active' : '' ?>">Preguntas</a>
        <a href="<?= e(url('admin/resultados.php')) ?>" class="<?= $activeMenu === 'resultados' ? 'active' : '' ?>">Resultados</a>
        <a href="<?= e(url('admin/estadisticas.php')) ?>" class="<?= $activeMenu === 'estadisticas' ? 'active' : '' ?>">Estadísticas</a>
        <a href="<?= e(url('admin/configuracion.php')) ?>" class="<?= $activeMenu === 'configuracion' ? 'active' : '' ?>">Configuración</a>
        <a href="<?= e(url('auth/logout.php')) ?>" class="nav-logout">Cerrar sesión</a>
    </nav>

    <div class="sidebar-user">
        <span class="avatar"><?= e(mb_substr($fullName, 0, 1)) ?></span>
        <div>
            <strong><?= e($fullName) ?></strong>
            <small>Administrador</small>
        </div>
    </div>
</aside>

<header class="topbar">
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú">☰</button>
    <div class="topbar-title">
        <h1><?= e($pageTitle) ?></h1>
    </div>
</header>
