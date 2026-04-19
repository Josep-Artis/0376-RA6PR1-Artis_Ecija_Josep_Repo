<?php
/**
 * index.php
 * Punto de entrada principal de Vestigia CheckIn
 * Redirige al dashboard si hay sesión activa, o al login si no
 */

require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/pages/main.php');
} else {
    header('Location: ' . APP_URL . '/pages/login.php');
}
exit;