<?php
/**
 * logout.php
 * Cierra la sesión del usuario y redirige al login
 * Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

logout();