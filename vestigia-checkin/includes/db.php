<?php
/**
 * db.php
 * Conexión a la base de datos mediante PDO
 * Vestigia CheckIn
 */

require_once dirname(__DIR__) . '/config.php';

/**
 * Obtiene la conexión PDO a la base de datos.
 * Usa el patrón Singleton para reutilizar la conexión.
 *
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En producción no mostrar el mensaje real
            error_log('Error de conexión BD: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión con la base de datos.']));
        }
    }

    return $pdo;
}