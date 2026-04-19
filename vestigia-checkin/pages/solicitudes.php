<?php
/**
 * solicitudes.php
 * Página de solicitudes (vacaciones, bajas, cambios de horario, teletrabajo)
 * Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/funciones.php';

requireLogin();

$userId  = userId();\n$pdo     = getDB();
$vista   = $_GET['vista'] ?? 'mias';
$tipoFiltro = $_GET['tipo'] ?? '';
$mensaje = '';
$error   = '';

// ── Procesar nueva solicitud ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if (!validarCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $accion = $_POST['accion'];

        if ($accion === 'nueva_solicitud') {
            $tipo        = $_POST['tipo'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin    = $_POST['fecha_fin'] ?? '';

            $tiposValidos = ['vacaciones','baja','cambio_horario','teletrabajo'];
            if (!in_array($tipo, $tiposValidos)) {
                $error = 'Tipo de solicitud inválido.';
            } elseif (!$descripcion) {
                $error = 'La descripción es obligatoria.';
            } else {
                if (crearSolicitud($userId, $tipo, $descripcion, $fechaInicio, $fechaFin)) {
                    $mensaje = 'Solicitud enviada correctamente. Recibirás una respuesta pronto.';
                } else {
                    $error = 'Error al enviar la solicitud. Inténtalo de nuevo.';
                }
            }
        }

        // Aprobar/rechazar solicitud (solo admins)
        elseif (in_array($accion, ['aprobar', 'rechazar']) && tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])) {
            $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
            $estado      = $accion === 'aprobar' ? 'aprobado' : 'rechazado';
            $stmt = $pdo->prepare(
                "UPDATE solicitudes SET estado = ?, aprobado_por = ?, fecha_resolucion = NOW()
                 WHERE id = ?"
            );
            if ($stmt->execute([$estado, $userId, $solicitudId])) {
                $mensaje = 'Solicitud ' . ($estado === 'aprobado' ? 'aprobada' : 'rechazada') . ' correctamente.';

                // Si es vacaciones aprobada, crear entrada en tabla vacaciones
                if ($estado === 'aprobado') {
                    $s = $pdo->prepare("SELECT * FROM solicitudes WHERE id = ?");
                    $s->execute([$solicitudId]);
                    $sol = $s->fetch();
                    if ($sol && $sol['tipo'] === 'vacaciones' && $sol['fecha_inicio'] && $sol['fecha_fin']) {
                        $pdo->prepare(
                            "INSERT INTO vacaciones (user_id, fecha_inicio, fecha_fin, estado, aprobado_por)
                             VALUES (?, ?, ?, 'aprobado', ?)"
                        )->execute([$sol['user_id'], $sol['fecha_inicio'], $sol['fecha_fin'], $userId]);
                    }
                }
            } else {
                $error = 'Error al procesar la solicitud.';
            }
        }
    }
}

// ── Obtener solicitudes ───────────────────────────────────────────────────────
if ($vista === 'admin' && tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])) {
    // Vista admin: todas las solicitudes (filtradas por depto si subadmin)
    if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])) {
        $stmt = $pdo->query(
            "SELECT s.*, u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM solicitudes s INNER JOIN users u ON u.id = s.user_id
             WHERE s.estado = 'pendiente'
             ORDER BY s.fecha DESC"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM solicitudes s INNER JOIN users u ON u.id = s.user_id
             WHERE s.estado = 'pendiente' AND u.departamento_id = ?
             ORDER BY s.fecha DESC"
        );
        $stmt->execute([userDepto()]);
    }
    $solicitudes = $stmt->fetchAll();
} else {
    // Vista usuario: solo las propias
    $sql = "SELECT s.* FROM solicitudes s WHERE s.user_id = ?";
    $params = [$userId];
    if ($tipoFiltro) {
        $sql .= " AND s.tipo = ?";
        $params[] = $tipoFiltro;
    }
    $sql .= " ORDER BY s.fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitudes = $stmt->fetchAll();
}

$etiquetasTipo = [
    'vacaciones'     => '🌴 Vacaciones',
    'baja'           => '🤒 Baja médica',
    'cambio_horario' => '📅 Cambio de horario',
    'teletrabajo'    => '🏠 Teletrabajo',
];
$etiquetasEstado = [
    'pendiente' => ['label' => 'Pendiente', 'clase' => 'badge-amarillo'],
    'aprobado'  => ['label' => 'Aprobado',  'clase' => 'badge-verde'],
    'rechazado' => ['label' => 'Rechazado', 'clase' => 'badge-rojo'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes — Vestigia CheckIn</title>
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
                <h2>📨 Solicitudes</h2>
                <p>Gestiona tus solicitudes de vacaciones, bajas y cambios de horario.</p>
            </div>

            <?php if ($mensaje): ?>
                <div class="alerta alerta-exito"><span>✓</span> <?= e($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alerta alerta-error"><span>✕</span> <?= e($error) ?></div>
            <?php endif; ?>

            <!-- Tabs: Mis solicitudes / Vista admin -->
            <div class="tabs-wrapper">
                <div class="tabs">
                    <button class="tab-btn <?= $vista !== 'admin' ? 'activo' : '' ?>" data-tab="mias">Mis solicitudes</button>
                    <?php if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])): ?>
                    <button class="tab-btn <?= $vista === 'admin' ? 'activo' : '' ?>" data-tab="admin">
                        Pendientes de aprobación
                    </button>
                    <?php endif; ?>
                    <button class="tab-btn" data-tab="nueva">+ Nueva solicitud</button>
                </div>

                <!-- Tab: Mis solicitudes -->
                <div id="tab-mias" class="tab-panel <?= $vista !== 'admin' ? 'activo' : '' ?>">
                    <div class="card">
                        <div class="card-titulo">📋 Mis solicitudes</div>
                        <?php if ($solicitudes && $vista !== 'admin'): ?>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Fechas</th>
                                        <th>Enviada</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $s): ?>
                                    <tr>
                                        <td><?= $etiquetasTipo[$s['tipo']] ?? e($s['tipo']) ?></td>
                                        <td style="max-width:280px;"><?= e($s['descripcion']) ?></td>
                                        <td>
                                            <?php if ($s['fecha_inicio'] && $s['fecha_fin']): ?>
                                                <?= date('d/m/Y', strtotime($s['fecha_inicio'])) ?>
                                                – <?= date('d/m/Y', strtotime($s['fecha_fin'])) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($s['fecha'])) ?></td>
                                        <td>
                                            <?php $est = $etiquetasEstado[$s['estado']] ?? ['label'=>$s['estado'],'clase'=>'badge-gris']; ?>
                                            <span class="badge <?= $est['clase'] ?>"><?= $est['label'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php elseif ($vista !== 'admin'): ?>
                            <p style="color:var(--texto-suave);font-size:0.9rem;">No tienes solicitudes enviadas.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Pendientes (admin) -->
                <?php if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])): ?>
                <div id="tab-admin" class="tab-panel <?= $vista === 'admin' ? 'activo' : '' ?>">
                    <div class="card">
                        <div class="card-titulo">✅ Solicitudes pendientes de aprobación</div>
                        <?php if ($solicitudes && $vista === 'admin'): ?>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Fechas</th>
                                        <th>Fecha solicitud</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $s): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($s['usuario_nombre']) ?></strong><br>
                                            <span style="font-size:0.78rem;color:var(--texto-suave);"><?= e($s['usuario_email']) ?></span>
                                        </td>
                                        <td><?= $etiquetasTipo[$s['tipo']] ?? e($s['tipo']) ?></td>
                                        <td style="max-width:240px;"><?= e($s['descripcion']) ?></td>
                                        <td>
                                            <?php if ($s['fecha_inicio']): ?>
                                                <?= date('d/m/Y', strtotime($s['fecha_inicio'])) ?>
                                                <?= $s['fecha_fin'] ? '– ' . date('d/m/Y', strtotime($s['fecha_fin'])) : '' ?>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($s['fecha'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="accion" value="aprobar">
                                                <input type="hidden" name="solicitud_id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-exito btn-sm">✓ Aprobar</button>
                                            </form>
                                            <form method="POST" style="display:inline;margin-left:0.25rem;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="accion" value="rechazar">
                                                <input type="hidden" name="solicitud_id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-peligro btn-sm"
                                                        data-confirmar="¿Rechazar esta solicitud?">✕ Rechazar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p style="color:var(--texto-suave);font-size:0.9rem;">No hay solicitudes pendientes. ¡Todo al día!</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tab: Nueva solicitud -->
                <div id="tab-nueva" class="tab-panel">
                    <div class="card" style="max-width:600px;">
                        <div class="card-titulo">📝 Nueva solicitud</div>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="accion" value="nueva_solicitud">

                            <div class="form-grupo">
                                <label for="tipo">Tipo de solicitud *</label>
                                <select name="tipo" id="tipo" class="form-control" required>
                                    <option value="">— Selecciona —</option>
                                    <?php foreach ($etiquetasTipo as $val => $etiq): ?>
                                        <option value="<?= $val ?>" <?= ($tipoFiltro === $val) ? 'selected' : '' ?>>
                                            <?= $etiq ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-grupo">
                                <label for="descripcion">Descripción *</label>
                                <textarea name="descripcion" id="descripcion" class="form-control"
                                          rows="4" required
                                          placeholder="Describe los detalles de tu solicitud..."></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-grupo">
                                    <label for="fecha_inicio">Fecha inicio</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio"
                                           class="form-control" min="<?= date('Y-m-d') ?>">
                                    <span class="form-ayuda">Opcional según el tipo de solicitud</span>
                                </div>
                                <div class="form-grupo">
                                    <label for="fecha_fin">Fecha fin</label>
                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                </div>
                            </div>

                            <div class="alerta alerta-info" style="margin-bottom:1rem;">
                                <span>ℹ</span>
                                <div>
                                    <strong>Recuerda:</strong> Las vacaciones se solicitan en enero para todo el año.
                                    Los cambios de horario se comunican trimestralmente.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primario">📨 Enviar solicitud</button>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<?php if ($vista === 'admin'): ?>
<script>
    // Activar tab admin si la URL lo indica
    document.addEventListener('DOMContentLoaded', () => {
        const tabAdmin = document.querySelector('[data-tab="admin"]');
        if (tabAdmin) tabAdmin.click();
    });
</script>
<?php elseif ($tipoFiltro): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabNueva = document.querySelector('[data-tab="nueva"]');
        if (tabNueva) tabNueva.click();
    });
</script>
<?php endif; ?>
</body>
</html>