<?php
// actualizar_estado.php
session_start();
require_once 'conexion.php';

// Seguridad: Solo el Administrador (Limber) puede cambiar estados de citas
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['nuevo_estado'])) {
    $id_turno     = intval($_GET['id']);
    $nuevo_estado = $_GET['nuevo_estado'];

    // Validar que el estado sea uno de los permitidos en la base de datos
    $estados_validos = ['Pendiente', 'Confirmado', 'Cancelado', 'Completo'];
    if (in_array($nuevo_estado, $estados_validos)) {
        try {
            $stmt = $pdo->prepare("UPDATE turnos SET estado = :est WHERE id_turno = :id");
            $stmt->execute(['est' => $nuevo_estado, 'id' => $id_turno]);
        } catch (\PDOException $e) {
            // Manejo de excepciones silencioso para la redirección
        }
    }
}

// Redirigir de vuelta al reporte de citas al instante
header("Location: ver_citas.php");
exit;
