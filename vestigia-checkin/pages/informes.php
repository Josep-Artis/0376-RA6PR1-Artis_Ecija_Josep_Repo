<?php
/**
 * informes.php
 * Página de informes de fichajes — Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/funciones.php';

requireLogin();

$userId  = userId();
$usuario = getUsuario($userId);
$pdo     = getDB();
$vista   = $_GET['vista'] ?? 'personal';

// ── Filtros de fecha ──────────────────────────────────────────────────────────
$filtro  = $_GET['filtro'] ?? 'mes';
$hoy     = date('Y-m-d');
switch ($filtro) {
    case 'hoy':    $desde = $hoy; $hasta = $hoy; break;
    case 'semana': $desde = date('Y-m-d', strtotime('monday this week')); $hasta = date('Y-m-d', strtotime('sunday this week')); break;
    case 'año':    $desde = date('Y-01-01'); $hasta = date('Y-12-31'); break;
    case 'rango':
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? $hoy;
        break;
    default: // mes
        $desde = date('Y-m-01');
        $hasta = date('Y-m-t');
}

// ── Filtro de usuario (admins) ────────────────────────────────────────────────
$filtroUsuario = (int)($_GET['user_id'] ?? 0);
if (!tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])) {
    $filtroUsuario = $userId; // Los usuarios normales solo ven sus datos
    $vista = 'personal';
} elseif (!$filtroUsuario) {
    $filtroUsuario = ($vista === 'personal') ? $userId : 0;
}

// ── Obtener fichajes ──────────────────────────────────────────────────────────
if ($filtroUsuario) {
    // Un usuario específico
    $stmt = $pdo->prepare(
        "SELECT f.*, u.nombre AS usuario_nombre, p.nombre AS proyecto_nombre
         FROM fichajes f
         LEFT JOIN users u ON u.id = f.user_id
         LEFT JOIN proyectos p ON p.id = f.proyecto_id
         WHERE f.user_id = ? AND f.fecha BETWEEN ? AND ?
         ORDER BY f.fecha DESC, f.hora_entrada DESC"
    );
    $stmt->execute([$filtroUsuario, $desde, $hasta]);
} else {
    // Todos (solo admins)
    if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])) {
        $stmt = $pdo->prepare(
            "SELECT f.*, u.nombre AS usuario_nombre, p.nombre AS proyecto_nombre
             FROM fichajes f
             LEFT JOIN users u ON u.id = f.user_id
             LEFT JOIN proyectos p ON p.id = f.proyecto_id
             WHERE f.fecha BETWEEN ? AND ?
             ORDER BY f.fecha DESC, u.nombre, f.hora_entrada DESC"
        );
        $stmt->execute([$desde, $hasta]);
    } else {
        // Subadmin: solo su departamento
        $stmt = $pdo->prepare(
            "SELECT f.*, u.nombre AS usuario_nombre, p.nombre AS proyecto_nombre
             FROM fichajes f
             INNER JOIN users u ON u.id = f.user_id
             LEFT JOIN proyectos p ON p.id = f.proyecto_id
             WHERE f.fecha BETWEEN ? AND ? AND u.departamento_id = ?
             ORDER BY f.fecha DESC, u.nombre, f.hora_entrada DESC"
        );
        $stmt->execute([$desde, $hasta, userDepto()]);
    }
}
$fichajes = $stmt->fetchAll();

// ── Estadísticas del periodo ──────────────────────────────────────────────────
$totalDias       = count($fichajes);
$diasTarde       = array_sum(array_column($fichajes, 'tarde'));
$diasTeletrabajo = array_sum(array_column($fichajes, 'teletrabajo'));
$minExtra        = array_sum(array_column($fichajes, 'horas_extra'));

// Lista de usuarios para el filtro (admins)
$usuarios = [];
if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])) {
    $usuarios = getUsuarios();
} elseif (tieneRol([ROL_SUBADMIN])) {
    $usuarios = getUsuarios(userDepto());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes — Vestigia CheckIn</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include dirname(__DIR__) . '/includes/header.php'; ?>
        <main class="page-area">
            <div class="page-header">
                <h2>📊 Informes de Jornada</h2>
                <p>Consulta y exporta los registros de fichajes.</p>
            </div>

            <!-- Filtros -->
            <form method="GET" action="">
                <input type="hidden" name="vista" value="<?= e($vista) ?>">
                <div class="filtros-informe">
                    <!-- Filtro rápido de período -->
                    <div class="form-grupo">
                        <label>Período</label>
                        <select name="filtro" class="form-control" data-autosubmit>
                            <option value="hoy"    <?= $filtro==='hoy'    ?'selected':'' ?>>Hoy</option>
                            <option value="semana" <?= $filtro==='semana' ?'selected':'' ?>>Esta semana</option>
                            <option value="mes"    <?= $filtro==='mes'    ?'selected':'' ?>>Este mes</option>
                            <option value="año"    <?= $filtro==='año'    ?'selected':'' ?>>Este año</option>
                            <option value="rango"  <?= $filtro==='rango'  ?'selected':'' ?>>Rango personalizado</option>
                        </select>
                    </div>

                    <?php if ($filtro === 'rango'): ?>
                    <div class="form-grupo">
                        <label>Desde</label>
                        <input type="date" name="desde" class="form-control" value="<?= e($desde) ?>">
                    </div>
                    <div class="form-grupo">
                        <label>Hasta</label>
                        <input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>">
                    </div>
                    <?php endif; ?>

                    <?php if ($usuarios): ?>
                    <div class="form-grupo">
                        <label>Empleado</label>
                        <select name="user_id" class="form-control" data-autosubmit>
                            <option value="0">— Todos —</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $filtroUsuario===$u['id']?'selected':'' ?>>
                                    <?= e($u['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if ($filtro === 'rango'): ?>
                    <div class="form-grupo" style="align-self:flex-end;">
                        <button type="submit" class="btn btn-primario">🔍 Filtrar</button>
                    </div>
                    <?php endif; ?>

                    <!-- Botones de exportación -->
                    <div class="form-grupo" style="align-self:flex-end;margin-left:auto;">
                        <a href="<?= APP_URL ?>/api/informes.php?formato=pdf&filtro=<?= urlencode($filtro) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&user_id=<?= $filtroUsuario ?>"
                           class="btn btn-acento btn-sm" target="_blank">📄 PDF</a>
                        <a href="<?= APP_URL ?>/api/informes.php?formato=excel&filtro=<?= urlencode($filtro) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&user_id=<?= $filtroUsuario ?>"
                           class="btn btn-secundario btn-sm" style="margin-left:0.25rem;">📊 Excel</a>
                    </div>
                </div>
            </form>

            <!-- Estadísticas del período -->
            <div class="stats-grid" style="margin-bottom:1.25rem;">
                <div class="stat-card">
                    <div class="stat-valor"><?= $totalDias ?></div>
                    <div class="stat-etiqueta">Días fichados</div>
                </div>
                <div class="stat-card acento">
                    <div class="stat-valor"><?= $diasTarde ?></div>
                    <div class="stat-etiqueta">Llegadas tarde</div>
                </div>
                <div class="stat-card verde">
                    <div class="stat-valor"><?= $diasTeletrabajo ?></div>
                    <div class="stat-etiqueta">Días teletrabajo</div>
                </div>
                <div class="stat-card">
                    <div class="stat-valor"><?= minutosAHoras($minExtra) ?></div>
                    <div class="stat-etiqueta">Horas extra</div>
                </div>
            </div>

            <!-- Tabla de fichajes -->
            <div class="card">
                <div class="card-titulo">
                    📋 Fichajes — <?= e($desde) ?> al <?= e($hasta) ?>
                </div>
                <?php if ($fichajes): ?>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <?php if ($filtroUsuario === 0 || tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])): ?>
                                <th>Empleado</th>
                                <?php endif; ?>
                                <th>Fecha</th>
                                <th>Proyecto</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                                <th>H. Extra</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fichajes as $f):
                                $minTrab = 0;
                                if ($f['hora_entrada'] && $f['hora_salida']) {
                                    $en = strtotime($f['fecha'].' '.$f['hora_entrada']);
                                    $sa = strtotime($f['fecha'].' '.$f['hora_salida']);
                                    $minTrab = (int)round(($sa-$en)/60);
                                }
                            ?>
                            <tr>
                                <?php if ($filtroUsuario === 0 || tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])): ?>
                                <td><?= e($f['usuario_nombre'] ?? '—') ?></td>
                                <?php endif; ?>
                                <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                                <td><?= e($f['proyecto_nombre'] ?? '—') ?></td>
                                <td><?= substr($f['hora_entrada'],0,5) ?></td>
                                <td>
                                    <?= $f['hora_salida'] ? substr($f['hora_salida'],0,5) : '<span class="badge badge-amarillo">Abierto</span>' ?>
                                </td>
                                <td><?= $minTrab ? minutosAHoras($minTrab) : '—' ?></td>
                                <td>
                                    <?php if ($f['horas_extra'] > 0): ?>
                                        <span class="badge badge-primario">+<?= minutosAHoras($f['horas_extra']) ?></span>
                                    <?php elseif ($f['horas_extra'] < 0): ?>
                                        <span class="badge badge-rojo"><?= minutosAHoras($f['horas_extra']) ?></span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td>
                                    <?= $f['tarde'] ? '<span class="badge badge-amarillo">⚠ Tarde</span>' : '<span class="badge badge-verde">✓ Puntual</span>' ?>
                                    <?= $f['teletrabajo'] ? '<span class="badge badge-azul">🏠</span>' : '' ?>
                                    <?php if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])): ?>
                                    <a href="<?= APP_URL ?>/api/informes.php?accion=editar_fichaje&id=<?= $f['id'] ?>"
                                       class="btn btn-secundario btn-sm" style="margin-left:0.25rem;">✏️</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p style="color:var(--texto-suave);font-size:0.9rem;padding:1rem 0;">
                        No hay fichajes registrados para el período seleccionado.
                    </p>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>