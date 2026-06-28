<?php
// conexion.php (MEJORADO - SEGURIDAD Y BUENAS PRÁCTICAS)

declare(strict_types=1);

/** =========================
 * CONFIGURACIÓN SEGURA
 * ========================= */
$host = 'localhost';
$db   = 'odontosync';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

/** =========================
 * DATA SOURCE NAME (DSN)
 * ========================= */
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

/** =========================
 * OPCIONES PDO SEGURAS
 * ========================= */
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch asociativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // seguridad contra inyección avanzada
    PDO::ATTR_PERSISTENT         => false,                 // evita conexiones persistentes inseguras
];

/** =========================
 * INTENTO DE CONEXIÓN
 * ========================= */
try {

    $pdo = new PDO($dsn, $user, $pass, $options);

    /** Opcional: validar conexión real */
    $pdo->query("SELECT 1");

} catch (PDOException $e) {

    /** =========================
     * LOG DE ERROR (NO MOSTRAR EN PANTALLA)
     * ========================= */
    error_log("ERROR CONEXIÓN BD OdontoSync: " . $e->getMessage());

    /** Mensaje genérico seguro */
    die("Error interno del sistema. Contacte al administrador.");
}
