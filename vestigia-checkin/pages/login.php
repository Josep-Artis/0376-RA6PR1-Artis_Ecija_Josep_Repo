<?php
/**
 * login.php
 * Página de inicio de sesión de Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/funciones.php';

// Si ya hay sesión, redirigir al dashboard
if (!empty($_SESSION['user_id'])) {
    redirigir(APP_URL . '/pages/main.php');
}

$error   = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';

    if (!validarCsrf($csrf)) {
        $error = 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    } elseif (!$email || !$password) {
        $error = 'Por favor, introduce tu email y contraseña.';
    } else {
        $resultado = login($email, $password);
        if ($resultado['success']) {
            redirigir(APP_URL . '/pages/main.php');
        } else {
            $error = $resultado['mensaje'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Vestigia CheckIn</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        .login-divider {
            border-top: 1px solid var(--borde);
            margin: 1.5rem 0 1rem;
            position: relative;
        }
        .login-divider span {
            position: absolute;
            top: -0.65rem;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 0.75rem;
            font-size: 0.78rem;
            color: var(--texto-suave);
        }
        .vestigia-motto {
            font-size: 0.78rem;
            font-style: italic;
            color: var(--texto-suave);
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <!-- Logo e identidad -->
        <div class="login-logo">
            <div class="logo-icon">📜</div>
            <h1>Vestigia CheckIn</h1>
            <p>Revista Internacional de Historia</p>
            <p class="vestigia-motto">Gestión de presencia y jornada laboral</p>
        </div>

        <!-- Mensaje de error -->
        <?php if ($error): ?>
            <div class="alerta alerta-error">
                <span>✕</span> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alerta alerta-exito">
                <span>✓</span> <?= e($success) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de login -->
        <form class="login-form" method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="tu@vestigia.com"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primario btn-bloque btn-lg" style="margin-top:0.5rem;">
                Acceder
            </button>
        </form>

        <div class="login-footer">
            <p>¿Problemas de acceso? Contacta con RRHH.</p>
        </div>
    </div>
</body>
</html>