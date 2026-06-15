<?php
// historial.php
session_start();
require_once 'conexion.php';

// Seguridad: Solo el Administrador (Limber) o el Odontólogo (Carlos) pueden gestionar el historial clínico
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] === 'paciente') {
    header("Location: login.php");
    exit;
}

$mensaje = "";

// Procesar el registro de la evolución médica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_historial'])) {
    $turno_id    = intval($_POST['turno_id']);
    $diagnostico = trim($_POST['diagnostico']);

    if (!empty($diagnostico)) {
        try {
            $pdo->beginTransaction();

            // 1. Actualizar las observaciones/diagnóstico en la tabla turnos
            $stmt = $pdo->prepare("UPDATE turnos SET observaciones = :diag, estado = 'Completo' WHERE id_turno = :id");
            $stmt->execute(['diag' => $diagnostico, 'id' => $turno_id]);

            $pdo->commit();
            $mensaje = "<div class='alert success'>✅ Expediente actualizado e Historia Clínica grabada correctamente.</div>";
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert error'>Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert error'>⚠️ El campo de diagnóstico clínico no puede estar vacío.</div>";
    }
}

// Cargar citas que ya fueron confirmadas y están listas para ser atendidas
$queryCitas = $pdo->query("SELECT t.id_turno, t.fecha_turno, u.nombre, u.apellido 
                           FROM turnos t 
                           JOIN usuarios u ON t.paciente_id = u.id_usuario 
                           WHERE t.estado = 'Confirmado' ORDER BY t.fecha_turno ASC");
$citas_atender = $queryCitas->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSync - Historia Clínica Digital</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7f6; padding: 40px; color: #334155; }
        .container { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 650px; margin: 0 auto; }
        h2 { color: #003366; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; font-weight: 700; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: #475569; }
        select, textarea { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
        select:focus, textarea:focus { border-color: #0066cc; }
        .btn-submit { width: 100%; padding: 12px; background: #003366; color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #002244; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #0066cc; text-decoration: none; font-weight: bold; font-size: 14px; }
        .btn-back:hover { text-decoration: underline; }
        .alert { padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .success { background: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="btn-back">⬅️ Volver al Panel de Control</a>
    <h2>Registro de Expediente e Historia Médica</h2>

    <?php echo $mensaje; ?>

    <form action="historial.php" method="POST">
        <input type="hidden" name="guardar_historial" value="1">
        
        <div class="form-group">
            <label for="turno_id">Seleccionar Paciente en Consulta</label>
            <select name="turno_id" id="turno_id" required>
                <option value="">-- Citas Confirmadas Disponibles --</option>
                <?php foreach ($citas_atender as $ca): ?>
                    <option value="<?php echo $ca['id_turno']; ?>">
                        #<?php echo $ca['id_turno']; ?> - <?php echo htmlspecialchars($ca['nombre'] . ' ' . $ca['apellido']); ?> (<?php echo date('d/m/Y', strtotime($ca['fecha_turno'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="diagnostico">Evolución, Diagnóstico y Tratamiento Clínico</label>
            <textarea id="diagnostico" name="diagnostico" rows="6" placeholder="Escriba las observaciones médicas, piezas dentales tratadas, recetas o procedimientos efectuados..." required></textarea>
        </div>

        <button type="submit" class="btn-submit">💾 Grabar en el Historial del Paciente</button>
    </form>
</div>

</body>
</html>
