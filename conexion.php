<?php
// conexion.php

$host = 'localhost';
$db   = 'odontosync';
$user = 'root';        // Usuario por defecto en XAMPP
$pass = '';            // Contraseña por defecto en XAMPP (vacía)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Descomenta la línea de abajo solo para probar si conecta correctamente
    // echo "Conexión exitosa a OdontoSync"; 
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
