<?php
/**
 * funciones.php
 * Funciones utilitarias generales de Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/db.php';

// ── Horarios ─────────────────────────────────────────────────────────────────

/**
 * Determina el horario de entrada/salida según tipo de jornada y fecha.
 *
 * @param string $tipoJornada completa|media_manana|media_tarde
 * @param string $fecha       YYYY-MM-DD (por defecto hoy)
 * @return array ['entrada' => 'HH:MM', 'salida' => 'HH:MM']
 */
function obtenerHorario(string $tipoJornada, string $fecha = ''): array {
    if (!$fecha) $fecha = date('Y-m-d');
    $mes = (int) date('m', strtotime($fecha));
    $esVerano = ($mes >= 6 && $mes < 9); // junio, julio, agosto

    switch ($tipoJornada) {
        case 'media_manana':
            return ['entrada' => HORA_ENTRADA_MANANA, 'salida' => HORA_SALIDA_MANANA];
        case 'media_tarde':
            return ['entrada' => HORA_ENTRADA_TARDE, 'salida' => HORA_SALIDA_TARDE];
        default: // completa
            if ($esVerano) {
                return ['entrada' => HORA_ENTRADA_VERANO, 'salida' => HORA_SALIDA_VERANO];
            }
            return ['entrada' => HORA_ENTRADA_NORMAL, 'salida' => HORA_SALIDA_NORMAL];
    }
}

/**
 * Calcula los minutos de retraso respecto a la hora de entrada esperada.
 *
 * @param string $horaEntrada    HH:MM:SS o HH:MM real
 * @param string $horaEsperada   HH:MM
 * @return int Minutos de retraso (0 si es puntual o anticipado)
 */
function calcularRetraso(string $horaEntrada, string $horaEsperada): int {
    $real    = strtotime(date('Y-m-d') . ' ' . $horaEntrada);
    $esperada = strtotime(date('Y-m-d') . ' ' . $horaEsperada);
    $diff = (int) round(($real - $esperada) / 60);
    return max(0, $diff);
}

/**
 * Calcula las horas extra de un fichaje respecto a la hora de salida esperada.
 * Devuelve los minutos extra (puede ser negativo si salió antes).
 */
function calcularHorasExtra(string $horaSalida, string $horaEsperadaSalida): int {
    $real     = strtotime(date('Y-m-d') . ' ' . $horaSalida);
    $esperada = strtotime(date('Y-m-d') . ' ' . $horaEsperadaSalida);
    return (int) round(($real - $esperada) / 60);
}

/**
 * Convierte minutos totales en formato legible "Xh Ym".
 */
function minutosAHoras(int $minutos): string {
    $signo = $minutos < 0 ? '-' : '';
    $minutos = abs($minutos);
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;
    return "{$signo}{$h}h {$m}m";
}

// ── Usuarios ─────────────────────────────────────────────────────────────────

/**
 * Devuelve los datos de un usuario por ID.
 */
function getUsuario(int $id): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.*, d.nombre AS departamento_nombre
         FROM users u
         LEFT JOIN departamentos d ON d.id = u.departamento_id
         WHERE u.id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Devuelve la lista de usuarios activos (no archivados).
 * Si se indica departamento_id, filtra por él.
 */
function getUsuarios(int $deptoId = 0): array {
    $pdo = getDB();
    if ($deptoId) {
        $stmt = $pdo->prepare(
            "SELECT u.*, d.nombre AS departamento_nombre
             FROM users u LEFT JOIN departamentos d ON d.id = u.departamento_id
             WHERE u.activo = 1 AND u.archivado = 0 AND u.departamento_id = ?
             ORDER BY u.nombre"
        );
        $stmt->execute([$deptoId]);
    } else {
        $stmt = $pdo->query(
            "SELECT u.*, d.nombre AS departamento_nombre
             FROM users u LEFT JOIN departamentos d ON d.id = u.departamento_id
             WHERE u.activo = 1 AND u.archivado = 0
             ORDER BY u.nombre"
        );
    }
    return $stmt->fetchAll();
}

// ── Proyectos ─────────────────────────────────────────────────────────────────

/**
 * Devuelve los proyectos asignados a un usuario.
 */
function getProyectosUsuario(int $userId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT p.* FROM proyectos p
         INNER JOIN proyecto_usuario pu ON pu.proyecto_id = p.id
         WHERE pu.user_id = ? AND p.activo = 1
         ORDER BY p.nombre"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// ── Fichajes ─────────────────────────────────────────────────────────────────

/**
 * Obtiene el fichaje abierto (sin hora_salida) de hoy para un usuario.
 */
function getFichajeAbierto(int $userId): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT * FROM fichajes
         WHERE user_id = ? AND fecha = ? AND hora_salida IS NULL
         LIMIT 1"
    );
    $stmt->execute([$userId, date('Y-m-d')]);
    return $stmt->fetch() ?: null;
}

/**
 * Obtiene todos los fichajes de un usuario en un rango de fechas.
 */
function getFichajesRango(int $userId, string $desde, string $hasta): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT f.*, p.nombre AS proyecto_nombre
         FROM fichajes f
         LEFT JOIN proyectos p ON p.id = f.proyecto_id
         WHERE f.user_id = ? AND f.fecha BETWEEN ? AND ?
         ORDER BY f.fecha DESC, f.hora_entrada DESC"
    );
    $stmt->execute([$userId, $desde, $hasta]);
    return $stmt->fetchAll();
}

// ── Solicitudes ───────────────────────────────────────────────────────────────

/**
 * Crea una nueva solicitud.
 */
function crearSolicitud(int $userId, string $tipo, string $descripcion, string $fechaInicio = '', string $fechaFin = ''): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "INSERT INTO solicitudes (user_id, tipo, descripcion, estado, fecha, fecha_inicio, fecha_fin)
         VALUES (?, ?, ?, 'pendiente', NOW(), ?, ?)"
    );
    return $stmt->execute([$userId, $tipo, $descripcion, $fechaInicio ?: null, $fechaFin ?: null]);
}

// ── Email con PHPMailer ───────────────────────────────────────────────────────

/**
 * Envía un email usando PHPMailer (si está instalado).
 * Silencioso si PHPMailer no existe.
 */
function enviarEmail(string $para, string $asunto, string $cuerpoHtml): bool {
    $phpmailerPath = dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (!file_exists($phpmailerPath)) {
        error_log("PHPMailer no encontrado. Email no enviado a: $para");
        return false;
    }
    require_once $phpmailerPath;
    require_once dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($para);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Error al enviar email: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notifica retraso al subadmin y RRHH.
 */
function notificarRetraso(int $userId, int $minutosRetraso): void {
    $usuario = getUsuario($userId);
    if (!$usuario) return;

    $pdo = getDB();
    // Buscar subadmin y admin_rrhh del mismo departamento
    $stmt = $pdo->prepare(
        "SELECT email, nombre FROM users
         WHERE (rol = 'subadmin' AND departamento_id = ?)
            OR rol = 'admin_rrhh'
         AND activo = 1 AND archivado = 0"
    );
    $stmt->execute([$usuario['departamento_id']]);
    $receptores = $stmt->fetchAll();

    $asunto = "[Vestigia CheckIn] Retraso de {$usuario['nombre']}";
    $cuerpo = "
        <p>El empleado <strong>{$usuario['nombre']}</strong> ha fichado con <strong>{$minutosRetraso} minutos de retraso</strong>
        el día " . date('d/m/Y') . " a las " . date('H:i') . ".</p>
        <p>Vestigia CheckIn</p>
    ";
    foreach ($receptores as $r) {
        enviarEmail($r['email'], $asunto, $cuerpo);
    }
}

// ── Seguridad / Utilidades ────────────────────────────────────────────────────

/**
 * Escapa HTML para evitar XSS.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Devuelve un token CSRF y lo guarda en sesión.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF enviado en el formulario.
 */
function validarCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirige a una URL.
 */
function redirigir(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Devuelve la URL base de la app.
 */
function baseUrl(string $ruta = ''): string {
    return APP_URL . '/' . ltrim($ruta, '/');
}

/**
 * Formatea una fecha en español: "lunes, 18 de abril de 2026".
 */
function fechaEspanol(string $fecha): string {
    $dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses  = ['','enero','febrero','marzo','abril','mayo','junio',
               'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts   = strtotime($fecha);
    $dia  = $dias[(int)date('w', $ts)];
    $num  = (int)date('j', $ts);
    $mes  = $meses[(int)date('n', $ts)];
    $anio = date('Y', $ts);
    return "{$dia}, {$num} de {$mes} de {$anio}";
}

/**
 * Devuelve los departamentos disponibles.
 */
function getDepartamentos(): array {
    $pdo = getDB();
    return $pdo->query("SELECT * FROM departamentos ORDER BY nombre")->fetchAll();
}

/**
 * Indica si hoy es jornada de verano.
 */
function esJornadaVerano(string $fecha = ''): bool {
    if (!$fecha) $fecha = date('Y-m-d');
    $mes = (int) date('m', strtotime($fecha));
    return ($mes >= 6 && $mes < 9);
}