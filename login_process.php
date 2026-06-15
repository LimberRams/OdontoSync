<?php
// login_process.php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibe el alias limpio sin arrobas
    $usuario_alias = trim($_POST['usuario_alias']);
    $password      = trim($_POST['password']);

    if (empty($usuario_alias) || empty($password)) {
        header("Location: login.php?error=campos_vacios");
        exit;
    }

    // Unimos de forma transparente el alias con el dominio para la base de datos
    $email_completo = $usuario_alias . "@odontosync.com";

    try {
        // Consulta segura mediante PDO (Validación de Caja Blanca)
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, apellido, password, rol FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email_completo]);
        $user = $stmt->fetch();

        if ($user) {
            // Validación de credenciales
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['nombre']     = $user['nombre'];
                $_SESSION['apellido']   = $user['apellido'];
                $_SESSION['rol']        = $user['rol'];

                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: login.php?error=password_incorrecto");
                exit;
            }
        } else {
            header("Location: login.php?error=usuario_no_encontrado");
            exit;
        }
    } catch (\PDOException $e) {
        header("Location: login.php?error=error_servidor");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
