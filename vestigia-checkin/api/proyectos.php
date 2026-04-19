<?php
/**
 * api/proyectos.php
 * API de gestión de proyectos — Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/funciones.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

$pdo    = getDB();
$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

// GET: listar proyectos del usuario o todos (admins)
if ($metodo === 'GET') {
    if ($accion === 'mis_proyectos') {
        $stmt = $pdo->prepare(
            "SELECT p.* FROM proyectos p
             INNER JOIN proyecto_usuario pu ON pu.proyecto_id = p.id
             WHERE pu.user_id = ? AND p.activo = 1
             ORDER BY p.nombre"
        );
        $stmt->execute([userId()]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($accion === 'todos' && tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH, ROL_SUBADMIN])) {
        if (tieneRol([ROL_SUPERADMIN, ROL_ADMIN_RRHH])) {
            $stmt = $pdo->query(
                "SELECT p.*, d.nombre AS departamento_nombre
                 FROM proyectos p LEFT JOIN departamentos d ON d.id = p.departamento_id
                 ORDER BY p.activo DESC, p.nombre"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT p.*, d.nombre AS departamento_nombre
                 FROM proyectos p LEFT JOIN departamentos d ON d.id = p.departamento_id
                 WHERE p.departamento_id = ? OR p.departamento_id IS NULL
                 ORDER BY p.activo DESC, p.nombre"
            );
            $stmt->execute([userDepto()]);
        }
        echo json_encode($stmt->fetchAll());
        exit;
    }
}

// POST: crear/editar/archivar proyecto
if ($metodo === 'POST' && tieneRol([ROL_SUPERADMIN, ROL_SUBADMIN])) {
    $datos  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $accion = $datos['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre    = trim($datos['nombre'] ?? '');
        $deptoId   = (int)($datos['departamento_id'] ?? 0) ?: null;
        $fechaIni  = $datos['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin  = $datos['fecha_fin'] ?? null;
        $usuarios  = $datos['usuarios'] ?? [];

        if (!$nombre) { echo json_encode(['error' => 'El nombre es obligatorio.']); exit; }

        $stmt = $pdo->prepare(
            "INSERT INTO proyectos (nombre, departamento_id, activo, fecha_inicio, fecha_fin)
             VALUES (?, ?, 1, ?, ?)"
        );
        $stmt->execute([$nombre, $deptoId, $fechaIni, $fechaFin ?: null]);
        $proyId = $pdo->lastInsertId();

        // Asignar usuarios
        foreach ($usuarios as $uid) {
            $pdo->prepare("INSERT IGNORE INTO proyecto_usuario (proyecto_id, user_id) VALUES (?, ?)")
                ->execute([$proyId, (int)$uid]);
        }

        echo json_encode(['ok' => true, 'id' => $proyId]);
        exit;
    }

    if ($accion === 'editar') {
        $id       = (int)($datos['id'] ?? 0);
        $nombre   = trim($datos['nombre'] ?? '');
        $activo   = (int)($datos['activo'] ?? 1);
        $fechaFin = $datos['fecha_fin'] ?? null;
        $usuarios = $datos['usuarios'] ?? null;

        if (!$id || !$nombre) { echo json_encode(['error' => 'Datos incompletos.']); exit; }

        $pdo->prepare("UPDATE proyectos SET nombre = ?, activo = ?, fecha_fin = ? WHERE id = ?")
            ->execute([$nombre, $activo, $fechaFin ?: null, $id]);

        if ($usuarios !== null) {
            $pdo->prepare("DELETE FROM proyecto_usuario WHERE proyecto_id = ?")->execute([$id]);
            foreach ($usuarios as $uid) {
                $pdo->prepare("INSERT IGNORE INTO proyecto_usuario (proyecto_id, user_id) VALUES (?, ?)")
                    ->execute([$id, (int)$uid]);
            }
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    if ($accion === 'archivar') {
        $id = (int)($datos['id'] ?? 0);
        $pdo->prepare("UPDATE proyectos SET activo = 0 WHERE id = ?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Acción no válida o sin permisos.']);