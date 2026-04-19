<?php
/**
 * auth.php
 * Funciones de autenticación y control de sesión
 * Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Inicia sesión con email y contraseña.
 * Devuelve array con 'success' y 'mensaje'.
 */
function login(string $email, string $password): array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT id, nombre, email, password, rol, departamento_id, tipo_jornada, foto, activo, archivado
         FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'mensaje' => 'Credenciales incorrectas.'];
    }
    if ($user['archivado']) {
        return ['success' => false, 'mensaje' => 'Esta cuenta ha sido archivada. Contacta con RRHH.'];
    }
    if (!$user['activo']) {
        return ['success' => false, 'mensaje' => 'Tu cuenta está desactivada. Contacta con RRHH.'];
    }
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'mensaje' => 'Credenciales incorrectas.'];
    }

    // Regenerar ID de sesión para prevenir fijación de sesión
    session_regenerate_id(true);

    // Guardar datos del usuario en sesión
    $_SESSION['user_id']        = $user['id'];
    $_SESSION['user_nombre']    = $user['nombre'];
    $_SESSION['user_email']     = $user['email'];
    $_SESSION['user_rol']       = $user['rol'];
    $_SESSION['user_depto']     = $user['departamento_id'];
    $_SESSION['user_jornada']   = $user['tipo_jornada'];
    $_SESSION['user_foto']      = $user['foto'];
    $_SESSION['login_time']     = time();

    return ['success' => true, 'mensaje' => 'Acceso correcto.'];
}

/**
 * Cierra la sesión del usuario actual.
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: ' . APP_URL . '/pages/login.php');
    exit;
}

/**
 * Comprueba si el usuario está autenticado.
 * Si no lo está, redirige al login.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/pages/login.php');
        exit;
    }
    // Comprobar timeout de sesión
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        logout();
    }
    // Renovar timestamp
    $_SESSION['login_time'] = time();
}

/**
 * Comprueba si el usuario tiene al menos uno de los roles indicados.
 * Si no, devuelve 403.
 *
 * @param string|array $roles Rol o array de roles permitidos
 */
function requireRol($roles): void {
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['user_rol'], $roles, true)) {
        http_response_code(403);
        include dirname(__DIR__) . '/pages/error403.php';
        exit;
    }
}

/**
 * Devuelve true si el usuario actual tiene alguno de los roles indicados.
 *
 * @param string|array $roles
 */
function tieneRol($roles): bool {
    $roles = (array) $roles;
    return in_array($_SESSION['user_rol'] ?? '', $roles, true);
}

/**
 * Devuelve el ID del usuario autenticado (o null si no hay sesión).
 */
function userId(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Devuelve el rol del usuario autenticado.
 */
function userRol(): string {
    return $_SESSION['user_rol'] ?? '';
}

/**
 * Devuelve el departamento del usuario autenticado.
 */
function userDepto(): ?int {
    return isset($_SESSION['user_depto']) ? (int) $_SESSION['user_depto'] : null;
}