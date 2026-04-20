<?php
/**
 * sidebar.php
 * Barra lateral de navegación — partial compartido
 * Vestigia CheckIn
 */

// Asegurarse de que las funciones de auth están disponibles
if (!function_exists('tieneRol')) {
    require_once dirname(__DIR__) . '/includes/auth.php';
}
$_paginaActual = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
    <!-- Cabecera del sidebar -->
    <div class="sidebar-header">
        <div class="sidebar-logo-icon">📜</div>
        <div>
            <div class="sidebar-title">Vestigia CheckIn</div>
            <div class="sidebar-subtitle">Revista Internacional de Historia</div>
        </div>
    </div>

    <!-- Menú de navegación -->
    <div class="sidebar-nav">
        <div class="nav-section-title">Principal</div>

        <a href="<?= APP_URL ?>/pages/main.php"
           class="nav-link <?= $_paginaActual === 'main.php' ? 'activo' : '' ?>">
            <span class="nav-icon">🏛️</span> Panel de control
        </a>

        <a href="<?= APP_URL ?>/pages/fichaje.php"
           class="nav-link <?= $_paginaActual === 'fichaje.php' ? 'activo' : '' ?>">
            <span class="nav-icon">🕐</span> Fichar
        </a>

        <a href="<?= APP_URL ?>/pages/horario.php"
           class="nav-link <?= $_paginaActual === 'horario.php' ? 'activo' : '' ?>">
            <span class="nav-icon">📅</span> Mi horario
        </a>

        <a href="<?= APP_URL ?>/pages/solicitudes.php"
           class="nav-link <?= $_paginaActual === 'solicitudes.php' ? 'activo' : '' ?>">
            <span class="nav-icon">📨</span> Solicitudes
        </a>

        <a href="<?= APP_URL ?>/pages/informes.php"
           class="nav-link <?= $_paginaActual === 'informes.php' ? 'activo' : '' ?>">
            <span class="nav-icon">📊</span> Mis informes
        </a>

        <?php if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])): ?>
        <div class="nav-section-title" style="margin-top:0.5rem;">Gestión</div>

        <a href="<?= APP_URL ?>/pages/informes.php?vista=equipo"
           class="nav-link <?= (isset($_GET['vista']) && $_GET['vista'] === 'equipo') ? 'activo' : '' ?>">
            <span class="nav-icon">👥</span> Equipo
        </a>

        <?php if (tieneRol([ROL_SUPERADMIN, ROL_SUBADMIN])): ?>
        <a href="<?= APP_URL ?>/pages/proyectos.php"
           class="nav-link <?= $_paginaActual === 'proyectos.php' ? 'activo' : '' ?>">
            <span class="nav-icon">📁</span> Proyectos
        </a>
        <?php endif; ?>

        <?php if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])): ?>
        <a href="<?= APP_URL ?>/pages/solicitudes.php?vista=admin"
           class="nav-link <?= (isset($_GET['vista']) && $_GET['vista'] === 'admin') ? 'activo' : '' ?>">
            <span class="nav-icon">✅</span> Aprobar solicitudes
        </a>
        <?php endif; ?>

        <a href="<?= APP_URL ?>/api/usuarios.php?vista=lista"
           class="nav-link">
            <span class="nav-icon">🗃️</span> Empleados
        </a>
        <?php endif; ?>

        <div class="nav-section-title" style="margin-top:0.5rem;">Cuenta</div>

        <a href="<?= APP_URL ?>/pages/perfil.php"
           class="nav-link <?= $_paginaActual === 'perfil.php' ? 'activo' : '' ?>">
            <span class="nav-icon">👤</span> Mi perfil
        </a>
    </div>

    <!-- Usuario en el pie del sidebar -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?php if (!empty($_SESSION['user_foto'])): ?>
                <img src="<?= APP_URL ?>/assets/img/uploads/<?= e($_SESSION['user_foto']) ?>" alt="Avatar">
            <?php else: ?>
                <?= strtoupper(mb_substr($_SESSION['user_nombre'] ?? 'U', 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-nombre"><?= e($_SESSION['user_nombre'] ?? '') ?></div>
            <div class="sidebar-user-rol"><?= e(str_replace('_', ' ', $_SESSION['user_rol'] ?? '')) ?></div>
        </div>
        <a href="<?= APP_URL ?>/pages/logout.php" class="btn-logout" title="Cerrar sesión">⏏</a>
    </div>
</nav>