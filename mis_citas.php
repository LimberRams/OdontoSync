<?php
// mis_citas.php
session_start();
require_once 'conexion.php';

// Seguridad: Solo los pacientes pueden auditar sus propios turnos
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'paciente') {
    header("Location: login.php");
    exit;
}

$paciente_id = $_SESSION['id_usuario'];

try {
    // Consulta relacional filtrada estrictamente por el ID del Paciente logueado (Seguridad de Caja Blanca)
    $sql = "SELECT t.id_turno, t.fecha_turno, t.hora_turno, t.estado, t.observaciones,
                   u_odo.nombre AS odo_nombre, u_odo.apellido AS odo_apellido, o.especialidad,
                   s.nombre_servicio, s.precio
            FROM turnos t
            JOIN odontologos o ON t.odontologo_id = o.id_odontologo
            JOIN usuarios u_odo ON o.id_odontologo = u_odo.id_usuario
            JOIN servicios s ON t.servicio_id = s.id_servicio
            WHERE t.paciente_id = :pac_id
            ORDER BY t.fecha_turno ASC, t.hora_turno ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pac_id' => $paciente_id]);
    $mis_turnos = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error al cargar tus citas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSync - Mis Citas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7f6; padding: 40px; color: #334155; }
        .table-container { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 1100px; margin: 0 auto; }
        h2 { color: #003366; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background-color: #003366; color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #f8fafc; }
        
        /* Insignias de Estado con Colores Estéticos */
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .pendiente { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .confirmado { background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .completo { background-color: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
        .cancelado { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        .btn-back { display: inline-block; margin-bottom: 20px; color: #0066cc; text-decoration: none; font-weight: bold; font-size: 14px; }
        .btn-back:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="table-container">
    <a href="dashboard.php" class="btn-back">⬅️ Volver al Panel de Control</a>
    <h2>Mis Turnos Odontológicos Registrados</h2>
    
    <?php if (count($mis_turnos) === 0): ?>
        <p style="color: #64748b; margin-top: 10px;">Aún no has agendado ninguna cita médica en el sistema.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Especialista Odontológico</th>
                    <th>Tratamiento Tratado</th>
                    <th>Costo Base</th>
                    <th>Plan / Modalidad de Pago</th>
                    <th>Estado de Cita</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mis_turnos as $t): ?>
                    <tr>
                        <td><strong><?php echo date('d/m/Y', strtotime($t['fecha_turno'])) . ' - ' . substr($t['hora_turno'], 0, 5); ?></strong></td>
                        <td>Dr. <?php echo htmlspecialchars($t['odo_nombre'] . ' ' . $t['odo_apellido'] . ' (' . $t['especialidad'] . ')'); ?></td>
                        <td><?php echo htmlspecialchars($t['nombre_servicio']); ?></td>
                        <td style="font-weight: bold; color: #16a34a;">Bs. <?php echo number_format($t['precio'], 2); ?></td>
                        <td style="color: #4b5563; font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($t['observaciones']); ?></td>
                        <td>
                            <?php 
                                $color = 'pendiente';
                                if ($t['estado'] === 'Confirmado') $color = 'confirmado';
                                if ($t['estado'] === 'Completo') $color = 'completo';
                                if ($t['estado'] === 'Cancelado') $color = 'cancelado';
                            ?>
                            <span class="status-badge <?php echo $color; ?>"><?php echo $t['estado']; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
